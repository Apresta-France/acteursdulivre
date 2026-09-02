<?php
$forumWysiwygName = (string) ($forumWysiwygName ?? 'body');
$forumWysiwygValue = (string) ($forumWysiwygValue ?? '');
$forumWysiwygPlaceholder = (string) ($forumWysiwygPlaceholder ?? '');
$forumWysiwygRows = max(5, (int) ($forumWysiwygRows ?? 7));
$forumWysiwygRequired = !isset($forumWysiwygRequired) || $forumWysiwygRequired;
?>
<div class="wysiwyg forum-wysiwyg" data-wysiwyg>
  <div class="wysiwyg-toolbar" hidden>
    <button type="button" data-wysiwyg-cmd="bold" aria-label="Gras" title="Gras"><strong>G</strong></button>
    <button type="button" data-wysiwyg-cmd="italic" aria-label="Italique" title="Italique"><em>I</em></button>
    <button type="button" data-wysiwyg-cmd="underline" aria-label="Souligné" title="Souligné"><span class="forum-wysiwyg-u">S</span></button>
    <button type="button" data-wysiwyg-cmd="formatBlock" data-wysiwyg-value="blockquote" aria-label="Citation" title="Citation">« »</button>
    <button type="button" data-wysiwyg-cmd="insertUnorderedList" aria-label="Liste à puces" title="Liste à puces">• Liste</button>
    <button type="button" data-wysiwyg-cmd="insertOrderedList" aria-label="Liste numérotée" title="Liste numérotée">1.</button>
  </div>
  <textarea
    class="textarea wysiwyg-source"
    name="<?= e($forumWysiwygName) ?>"
    rows="<?= $forumWysiwygRows ?>"
    <?= $forumWysiwygRequired ? 'required' : '' ?>
    placeholder="<?= e($forumWysiwygPlaceholder) ?>"
  ><?= e($forumWysiwygValue) ?></textarea>
  <div class="wysiwyg-editor" contenteditable="true" role="textbox" aria-multiline="true" hidden></div>
</div>
