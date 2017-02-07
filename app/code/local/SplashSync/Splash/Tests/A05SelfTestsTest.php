<?php

use Splash\Tests\Tools\BaseCase;

use Splash\Client\Splash;

/**
 * @abstract    Admin Test Suite - SelfTest Client Verifications
 *
 * @author SplashSync <contact@splashsync.com>
 */
class A05SelfTestsTest extends BaseCase {
    
    public function testFromLocalClass()
    {
        //====================================================================//
        //   Execute Action From Module  
        $Data = Splash::Local()->SelfTest();
echo "Hello!!";
$this->assertTrue(False);
        //====================================================================//
        //   Verify Response
        $this->VerifyResponse($Data);
    }

    
    public function testFromAdmin()
    {
        
        //====================================================================//
        //   Execute Action From Splash Server to Module  
        $Data = $this->GenericAction(SPL_S_ADMIN, SPL_F_GET_SELFTEST, __METHOD__);
        
        //====================================================================//
        //   Verify Response
        $this->VerifyResponse($Data);
        
    }
    
    public function VerifyResponse($Data)
    {
        //====================================================================//
        //   Render Logs if Fails*
        if ( !$Data) {
            fwrite(STDOUT, Splash::Log()->GetConsoleLog() );
        } 
        
        //====================================================================//
        //   Verify Response
        $this->assertIsSplashBool(  $Data       , "SelfTest");
        $this->assertNotEmpty(      $Data       , "SelfTest not Passed!! Check logs to see why!");
    }
    
}
