<?php if ($token = ConfigItem::getTokenByNamespace('google2fa', $staff->getId())) { ?>
    <tr>
        <td><?php echo __('QR Code'); ?>:
            <i class="help-tip icon-question-sign" href="#google2fa"></i>
        </td>
        <td>
        <?php
            $googleAuth = new GoogleAuth2FA;
            $qrCodeURL = $googleAuth->getQRCode($staff);
            if ($googleAuth->validateQRCode($staff)) {
         ?>
         <img src="<?php echo $qrCodeURL; ?>" alt="QR Code" />
        <?php } ?>
        </td>
    </tr>
<?php
}
