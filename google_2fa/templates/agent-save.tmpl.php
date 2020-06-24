<?php if ($token = ConfigItem::getConfigsByNamespace('staff.'.$staff->getId(), 'backend2fa', 'Google2FA')) { ?>
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
