<html>
<head prefix="og: http://ogp.me/ns#
         showgoer: http://ogp.me/ns/apps/showgoer#">
 <meta property="fb:app_id"           content="<?php echo FB_APP_ID; ?>" />
 <meta property="og:type"             content="showgoer:band" />
 <meta property="og:title"            content="<?php echo $band->name; ?>" />
 <meta property="og:description" content="" />
 <meta property="og:image"
       content="https://graph.facebook.com/<?php echo $band->page_fbid; ?>/picture" />
 <meta property="og:url" content="http://facebook.com/<?php echo $band->page_fbid; ?>" />
</head>
</html>
