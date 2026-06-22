<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>New Newsfeed Post</title>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:'Segoe UI',Arial,sans-serif;color:#374151;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;padding:32px 16px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  <!-- Header -->
  <tr>
    <td style="background:#2B2D42;border-radius:12px 12px 0 0;padding:24px 32px;">
      <span style="color:#ffffff;font-size:16px;font-weight:700;">Staff Portal — Newsfeed</span>
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="background:#ffffff;padding:28px 32px;border-radius:0 0 12px 12px;">
      <p style="margin:0 0 16px;font-size:15px;">
        <strong>{{ $authorName }}</strong> just posted on the company newsfeed.
      </p>

      @if($postTitle)
        <p style="margin:0 0 6px;font-size:18px;font-weight:700;color:#111827;">{{ $postTitle }}</p>
      @endif

      <table width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 20px;">
        <tr>
          <td style="background:#F8FAFC;border-left:4px solid #EF233C;border-radius:0 8px 8px 0;padding:14px 16px;font-size:14px;color:#4b5563;">
            {{ $excerpt }}
          </td>
        </tr>
      </table>

      <a href="{{ $url }}" style="display:inline-block;background:#EF233C;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:11px 22px;border-radius:10px;">
        Open the Newsfeed
      </a>

      <p style="margin:24px 0 0;font-size:12px;color:#9ca3af;">
        You're receiving this because you're a member of the team. Visit the Newsfeed in the Staff Portal to react and comment.
      </p>
    </td>
  </tr>

  <tr>
    <td style="padding:16px 32px;text-align:center;font-size:11px;color:#9ca3af;">
      BCF Staff Portal
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
