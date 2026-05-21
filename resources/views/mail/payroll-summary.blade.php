<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Payroll Summary</title>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:'Segoe UI',Arial,sans-serif;color:#374151;">

<!-- Wrapper -->
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;padding:32px 16px;">
<tr><td align="center">
<table width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;">

  <!-- ── Header ── -->
  <tr>
    <td style="background:#2B2D42;border-radius:12px 12px 0 0;padding:28px 32px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td>
            <!-- Logo block -->
            <table cellpadding="0" cellspacing="0">
              <tr>
                <td style="background:#EF233C;border-radius:10px;width:40px;height:40px;text-align:center;vertical-align:middle;">
                  <div style="width:20px;height:20px;background:white;border-radius:5px;margin:10px auto;"></div>
                </td>
                <td style="padding-left:12px;vertical-align:middle;">
                  <p style="margin:0;color:white;font-size:13px;font-weight:700;letter-spacing:0.3px;">Bespoke Garden Rooms Ballycastle</p>
                  <p style="margin:2px 0 0;color:#8D99AE;font-size:11px;">BCF Staff Portal</p>
                </td>
              </tr>
            </table>
          </td>
          <td align="right" style="vertical-align:top;">
            <span style="background:#EF233C;color:white;font-size:10px;font-weight:700;padding:4px 10px;border-radius:20px;letter-spacing:0.5px;text-transform:uppercase;">PAYROLL</span>
          </td>
        </tr>
      </table>

      <p style="margin:20px 0 4px;color:#8D99AE;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">Period</p>
      <p style="margin:0;color:white;font-size:20px;font-weight:800;">
        {{ \Carbon\Carbon::parse($periodFrom)->format('d M Y') }}
        &nbsp;–&nbsp;
        {{ \Carbon\Carbon::parse($periodTo)->format('d M Y') }}
      </p>
    </td>
  </tr>

  <!-- ── Stats bar ── -->
  <tr>
    <td style="background:#1E293B;padding:0 32px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding:16px 0;border-right:1px solid rgba(255,255,255,0.08);width:25%;">
            <p style="margin:0 0 2px;color:#8D99AE;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Staff</p>
            <p style="margin:0;color:white;font-size:18px;font-weight:800;">{{ $staffCount }}</p>
          </td>
          <td style="padding:16px 0 16px 20px;border-right:1px solid rgba(255,255,255,0.08);width:25%;">
            <p style="margin:0 0 2px;color:#8D99AE;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Total Hours</p>
            <p style="margin:0;color:white;font-size:18px;font-weight:800;font-family:monospace;">{{ number_format($totalHours, 2) }}h</p>
          </td>
          <td style="padding:16px 0 16px 20px;border-right:1px solid rgba(255,255,255,0.08);width:25%;">
            <p style="margin:0 0 2px;color:#8D99AE;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Gross Pay</p>
            <p style="margin:0;color:#EF233C;font-size:18px;font-weight:800;">£{{ number_format($totalGross, 2) }}</p>
          </td>
          <td style="padding:16px 0 16px 20px;width:25%;">
            <p style="margin:0 0 2px;color:#8D99AE;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Net Pay</p>
            <p style="margin:0;color:#4ADE80;font-size:18px;font-weight:800;">£{{ number_format($totalNet, 2) }}</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- ── Body ── -->
  <tr>
    <td style="background:white;padding:28px 32px;">

      <p style="margin:0 0 18px;font-size:13px;color:#374151;line-height:1.6;">
        Please find the approved payroll summary for the above period attached as a CSV file.
        The table below provides an overview of all {{ $staffCount }} payslip{{ $staffCount !== 1 ? 's' : '' }} included.
      </p>

      <!-- Payslip table -->
      <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;font-size:11px;">
        <thead>
          <tr style="background:#F8FAFC;">
            <th style="padding:9px 10px;text-align:left;color:#6B7280;font-weight:700;border-bottom:1px solid #E5E7EB;">Employee</th>
            <th style="padding:9px 10px;text-align:right;color:#6B7280;font-weight:700;border-bottom:1px solid #E5E7EB;">Shifts</th>
            <th style="padding:9px 10px;text-align:right;color:#6B7280;font-weight:700;border-bottom:1px solid #E5E7EB;">Reg h</th>
            <th style="padding:9px 10px;text-align:right;color:#6B7280;font-weight:700;border-bottom:1px solid #E5E7EB;">OT h</th>
            <th style="padding:9px 10px;text-align:right;color:#6B7280;font-weight:700;border-bottom:1px solid #E5E7EB;">Gross</th>
            <th style="padding:9px 10px;text-align:right;color:#6B7280;font-weight:700;border-bottom:1px solid #E5E7EB;">Deductions</th>
            <th style="padding:9px 10px;text-align:right;color:#6B7280;font-weight:700;border-bottom:1px solid #E5E7EB;">Net Pay</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $i => $row)
          <tr style="background:{{ $i % 2 === 0 ? '#FFFFFF' : '#F9FAFB' }};">
            <td style="padding:8px 10px;border-bottom:1px solid #F3F4F6;">
              <p style="margin:0;font-weight:600;color:#111827;">{{ $row['name'] }}</p>
              <p style="margin:0;font-size:10px;color:#9CA3AF;font-family:monospace;">{{ $row['employee_id'] }}</p>
            </td>
            <td style="padding:8px 10px;text-align:right;color:#6B7280;border-bottom:1px solid #F3F4F6;">{{ $row['shifts'] }}</td>
            <td style="padding:8px 10px;text-align:right;font-family:monospace;color:#374151;border-bottom:1px solid #F3F4F6;">{{ number_format($row['regular_hours'], 2) }}</td>
            <td style="padding:8px 10px;text-align:right;font-family:monospace;color:{{ $row['overtime_hours'] > 0 ? '#D97706' : '#9CA3AF' }};border-bottom:1px solid #F3F4F6;">{{ number_format($row['overtime_hours'], 2) }}</td>
            <td style="padding:8px 10px;text-align:right;font-weight:600;color:#111827;border-bottom:1px solid #F3F4F6;">
              @if($row['has_rate'])
                £{{ number_format($row['gross_pay'], 2) }}
              @else
                <span style="color:#F59E0B;font-size:10px;">No rate</span>
              @endif
            </td>
            <td style="padding:8px 10px;text-align:right;color:{{ $row['deductions'] > 0 ? '#EF4444' : '#9CA3AF' }};border-bottom:1px solid #F3F4F6;">
              {{ $row['deductions'] > 0 ? '−£' . number_format($row['deductions'], 2) : '—' }}
            </td>
            <td style="padding:8px 10px;text-align:right;font-weight:700;color:#16A34A;border-bottom:1px solid #F3F4F6;">
              @if($row['has_rate'])
                £{{ number_format($row['net_pay'], 2) }}
              @else
                <span style="color:#F59E0B;font-size:10px;">N/A</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
        <!-- Totals row -->
        <tfoot>
          <tr style="background:#F1F5F9;">
            <td style="padding:10px 10px;font-weight:700;color:#2B2D42;">Totals</td>
            <td colspan="3" style="padding:10px 10px;text-align:right;font-size:10px;color:#6B7280;font-family:monospace;">{{ number_format($totalHours, 2) }}h total</td>
            <td style="padding:10px 10px;text-align:right;font-weight:700;color:#EF233C;">£{{ number_format($totalGross, 2) }}</td>
            <td style="padding:10px 10px;"></td>
            <td style="padding:10px 10px;text-align:right;font-weight:700;color:#16A34A;">£{{ number_format($totalNet, 2) }}</td>
          </tr>
        </tfoot>
      </table>

      <!-- Note -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;background:#FEF9C3;border:1px solid #FDE047;border-radius:8px;">
        <tr>
          <td style="padding:12px 14px;">
            <p style="margin:0;font-size:11px;color:#713F12;line-height:1.6;">
              📎 <strong>CSV attachment</strong> — The attached file
              <strong>{{ $csvFilename }}</strong> contains the full payroll
              data including employee IDs, hourly rates, and individual shift breakdowns.
            </p>
          </td>
        </tr>
      </table>

    </td>
  </tr>

  <!-- ── Footer ── -->
  <tr>
    <td style="background:#F8FAFC;border-top:1px solid #E5E7EB;border-radius:0 0 12px 12px;padding:16px 32px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td>
            <p style="margin:0;font-size:10px;color:#9CA3AF;">
              Sent by <strong style="color:#6B7280;">{{ $sentByName }}</strong>
              via BCF Staff Portal on {{ now()->format('d M Y, H:i') }} UTC
            </p>
            <p style="margin:4px 0 0;font-size:10px;color:#9CA3AF;">
              This email is intended for payroll processing purposes only. Do not forward or distribute.
            </p>
          </td>
          <td align="right" style="vertical-align:top;">
            <span style="font-size:9px;color:#D1D5DB;font-weight:700;letter-spacing:1px;text-transform:uppercase;">INTERNAL</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>

</table>
</td></tr>
</table>

</body>
</html>
