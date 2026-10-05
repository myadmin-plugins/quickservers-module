<?php

declare(strict_types=1);

namespace Detain\MyAdminQuickservers\Tests;

use PHPUnit\Framework\TestCase;

/**
 * MyAdmin plan_2way §5.3, §6: the queue script is rendered from the kvm-vps
 * templates without the stored qs_rootpass/vps_rootpass columns (an envelope
 * after the flip); those templates only render rootpass/origrootpass.
 */
class QueueAssignTest extends TestCase
{
    public function testTheStoredRootpassColumnsStayOutOfTheAssign(): void
    {
        $src = (string) file_get_contents(dirname(__DIR__) . '/src/Plugin.php');
        $this->assertStringContainsString("\$smarty->assign(array_diff_key(\$serviceInfo, ['qs_rootpass' => true, 'vps_rootpass' => true]));", $src);
        $this->assertStringNotContainsString('$smarty->assign($serviceInfo);', $src);
        $kvm = dirname(__DIR__, 2) . '/myadmin-kvm-vps/templates';
        foreach (is_dir($kvm) ? glob($kvm . '/*.tpl') : [] as $tpl) {
            $this->assertDoesNotMatchRegularExpression('/\$(vps|qs)_rootpass\b/', (string) file_get_contents($tpl), basename($tpl));
        }
    }
}
