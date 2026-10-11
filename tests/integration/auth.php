<?php
return [
 'registration normalizes email and ignores admin role' => static function (): void {
  $db=test_db();
  try {
   $email='test_'.bin2hex(random_bytes(6)).'@example.test';
   $id=register_customer($db,['name'=>'Test Customer','email'=>strtoupper($email),'password'=>'flowers123','role'=>'admin']);
   $GLOBALS['_fixture_users'][]=$id;
   $query=$db->prepare('SELECT * FROM users WHERE id=?');$query->execute([$id]);$user=$query->fetch();
   assert_same($email,$user['email']);assert_same('customer',$user['role']);assert_true(password_verify('flowers123',$user['password_hash']));
   assert_throws(static fn()=>register_customer($db,['name'=>'Second','email'=>' '.$email.' ','password'=>'flowers123']),ValidationException::class);
   assert_same(null,authenticate_user($db,$email,'wrong-password'));
   assert_same($id,(int)authenticate_user($db,strtoupper($email),'flowers123')['id']);
  } finally {cleanup_fixtures($db);}
 },
 'registration rejects invalid fields' => static function (): void {
  $db=test_db();
  assert_throws(static fn()=>register_customer($db,['name'=>'','email'=>'broken','password'=>'tiny']),ValidationException::class);
 },
];
