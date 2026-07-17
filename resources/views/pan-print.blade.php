<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notice of Personnel Action</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
@import url('https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap');
*{ font-family: 'Courier'; font-weight: 700; }
body{ height: auto;  padding: 40px 15%;   display: flex; background-color: #F1F2F6;
    flex-direction: column;
    gap: 20px; }
.outer-border{ margin: 20px; background-color: white; outline: 35px solid white; border: 3px solid #385623; }
.inner-border{ gap:20px; border: 4px solid #385623; }
.inner-border h3{ text-align: center; font-size: 28px; margin-bottom: 10px; }
.inner-border h1{ font-size: 40px; }
.inner-border h2{ font-size: 32px; }
.print-header{ display: flex; flex-direction: column; align-items: center; }
.input-container{ display:grid; grid-template-columns: 1fr 1fr; }
table { background-color: transparent; table-layout: fixed; width: 100%; }
label, p{ font-size: 20px; }
td{ font-size: 23px; padding: 3px 10px; border: 2px solid #70ad47; border-collapse: collapse; }
span{ padding: 20px; font-size: 12px; font-weight: bold; text-align: center; color: red; background-color: #e2efd9; }
img{ width: 280px; margin: 40px 0 20px; }
.confirmation-field{ margin: 75px 0 25px; border-top: 2px solid; }
.signatories{ display: flex; flex-direction: column; gap: 20px; position: relative; }
.signatories img{ position: absolute; top: -30px; left: 10px; width: 120px; }
.home-btn{ font-family: 'Poppins' !important; font-weight: 500; }
.page-1 { display: none; }
.page-2 { display: none; }
.copy{ opacity: 0; right: 30px; }
.remarks-text { font-size: 15px; }

@media print {
  .print-action{ display: none !important; }
  body{ padding: 0 !important; background-color: white !important; }
  .outer-border{ border: 1px solid #385623 !important; }
  .inner-border{ border: 2px solid #385623 !important; gap: 10px; }
  img{ width: 180px; margin: 10px 0 20px; }
  .inner-border h3{ font-size: 17px; }
  .inner-border h1{ font-size: 25px; }
  .inner-border h2{ font-size: 22px; }
  td{ font-size: 15px; padding: 0 5px !important; border: 3px solid #70ad47; }
  .confirmation-field{ margin: 35px 0 15px; border-top: 1px solid; }
  .signatories{ gap: 10px; }
  .grid-cols-4 label{ font-size: 12px; }
  .grid-cols-4 p{ font-size: 12px; }
  label, p{ font-size: 14px; }
  span{ font-size: 8px; }
  .signatories img{ top: -10px; width: 90px; }
  .page{ page-break-after: always; }
  .page-1{ display: block !important; }
  .page-2{ display: block !important; }
  .copy{ opacity: 1; }
  .remarks-text { font-size: 11pt; }
  .remarks-text.remarks-sm { font-size: 9pt; }
  .remarks-text.remarks-md { font-size: 7pt; }
  .remarks-text.remarks-lg { font-size: 6pt; }

  .inner-border.compact-sm { gap: 7px; }
  .inner-border.compact-sm h3 { font-size: 14px; }
  .inner-border.compact-sm h1 { font-size: 20px; }
  .inner-border.compact-sm h2 { font-size: 17px; }
  .inner-border.compact-sm td { font-size: 12px; }
  .inner-border.compact-sm label,
  .inner-border.compact-sm p { font-size: 11px; }
  .inner-border.compact-sm img { width: 140px; margin: 5px 0 10px; }
  .inner-border.compact-sm .confirmation-field { margin: 22px 0 10px; }
  .inner-border.compact-sm .signatories img { width: 75px; top: -8px; }
  .inner-border.compact-sm span { font-size: 7px; padding: 10px; }

  .inner-border.compact-md { gap: 5px; }
  .inner-border.compact-md h3 { font-size: 12px; }
  .inner-border.compact-md h1 { font-size: 17px; }
  .inner-border.compact-md h2 { font-size: 14px; }
  .inner-border.compact-md td { font-size: 10px; }
  .inner-border.compact-md label,
  .inner-border.compact-md p { font-size: 10px; }
  .inner-border.compact-md img { width: 120px; margin: 3px 0 8px; }
  .inner-border.compact-md .confirmation-field { margin: 15px 0 8px; }
  .inner-border.compact-md .signatories img { width: 65px; top: -6px; }
  .inner-border.compact-md span { font-size: 6px; padding: 8px; }

  .inner-border.compact-lg { gap: 3px; }
  .inner-border.compact-lg h3 { font-size: 11px; }
  .inner-border.compact-lg h1 { font-size: 15px; }
  .inner-border.compact-lg h2 { font-size: 12px; }
  .inner-border.compact-lg td { font-size: 9px; }
  .inner-border.compact-lg label,
  .inner-border.compact-lg p { font-size: 9px; }
  .inner-border.compact-lg img { width: 100px; margin: 2px 0 6px; }
  .inner-border.compact-lg .confirmation-field { margin: 10px 0 5px; }
  .inner-border.compact-lg .signatories img { width: 55px; top: -5px; }
  .inner-border.compact-lg span { font-size: 5.5px; padding: 6px; }
}
</style>
</head>
<body>

<!-- PAGE 1 : EMPLOYEE COPY -->
<div class="page outer-border p-[3px] relative">
  <div class="print-action absolute right-[-190px] top-[-35px] w-32 bg-white text-black shadow-lg">
    <ul class="py-2">
      <li>
        <div class="home-btn text-left px-4 py-2 select-none cursor-pointer text-gray-500 text-[17px] hover:text-gray-800 hover:scale-105 hover:bg-gray-100 transition-transform duration-200" onclick="window.history.back()">
          <i class="fa-solid fa-arrow-right-to-bracket rotate-180 ml-1"></i> Back
        </div>
      </li>
      <li>
        <div class="home-btn text-left px-4 py-2 select-none cursor-pointer text-gray-500 text-[17px] hover:text-gray-800 hover:scale-105 hover:bg-gray-100 transition-transform duration-200" onclick="window.print()">
          <i class="fa-solid fa-print"></i> Print
        </div>
      </li>
    </ul>
  </div>

  <div class="inner-border px-3 py-5 flex flex-col items-center">
    <div class="copy absolute text-lg font-bold text-gray-400 tracking-widest">
      <p>EMPLOYEE COPY</p>
    </div>

    <div class="print-header">
      <img src="{{asset('images/BGC.png')}}" alt="">
      <h3 class="font-courier">
        BROOKSIDE FARMS CORPORATION <br>
        Anupul, Bamban, Tarlac
      </h3>
      <h1 class="font-courier">NOTICE OF PERSONNEL ACTION</h1>
      <h2 class="font-courier text-[#70ad47]">WAGE ORDER NO. 24</h2>
    </div>

    <table>
      <tr>
        <td>Name: Juan Dela Cruz</td>
        <td>Employee No: 2024-00123</td>
      </tr>
      <tr>
        <td>Date Hired: 03/15/2021</td>
        <td>Division: Production</td>
      </tr>
      <tr>
        <td>Employment Status: Regular</td>
        <td>Date of Effectivity: 08/01/2026</td>
      </tr>
    </table>

    <table class="text-center">
      <tr>
        <td class="!bg-[#e2efd9]">FROM</td>
        <td class="!bg-[#e2efd9]">ACTION REFERENCE</td>
        <td class="!bg-[#e2efd9]">TO</td>
      </tr>
      <tr>
        <td>Farm 1 - Anupul</td>
        <td class="!bg-[#e2efd9] capitalize">Place of Assignment</td>
        <td>Farm 3 - Bamban</td>
      </tr>
      <tr>
        <td>Maria Santos</td>
        <td class="!bg-[#e2efd9] capitalize">Immediate Head</td>
        <td>Pedro Reyes</td>
      </tr>
      <tr>
        <td>Level 3</td>
        <td class="!bg-[#e2efd9] capitalize">Job Level</td>
        <td>Level 4</td>
      </tr>
      <tr>
        <td>10 days</td>
        <td class="!bg-[#e2efd9] capitalize">Leave Credits</td>
        <td>15 days</td>
      </tr>
    </table>

    <table class="text-center">
      <tr><td class="!bg-[#e2efd9]">REMARKS AND OTHER CONSIDERATION</td></tr>
      <tr>
        <td>
          <div class="w-full text-center remarks-text" style="word-break: break-word; min-height: 1.5em;">
            Adjustment pursuant to Wage Order No. 24.<br>
            • Effective on the date indicated above.<br>
            • All other terms and conditions remain unchanged.
          </div>
        </td>
      </tr>
    </table>

    <table class="text-center">
      <tr><td class="!bg-[#e2efd9]">CONFIRMATION OF APPOINTMENT</td></tr>
      <tr>
        <td class="flex items-center justify-around">
          <div class="confirmation-field">(SIGNATURE OVER PRINTED NAME)</div>
          <div class="confirmation-field">(DATE RECEIVE)</div>
        </td>
      </tr>
    </table>

    <div class="grid grid-cols-4 text-sm w-full px-6">
      <div class="signatories">
        <label>Prepared By:</label>
        <div>
          <img src="storage/esign/hr.png" alt="Unavailable">
          <p>Ana Lopez</p>
          <p>HR Assistant</p>
        </div>
      </div>
      <div class="signatories">
        <label>Noted By:</label>
        <div>
          <img src="storage/esign/hra.png" alt="Unavailable">
          <p>Carlo Mendoza</p>
          <p>Head, Human Resources</p>
        </div>
      </div>
      <div class="signatories">
        <label>Recommended By:</label>
        <div>
          <img src="storage/esign/divisionhead.png" alt="Unavailable">
          <p>Ramon Garcia</p>
          <p>Division Head</p>
        </div>
      </div>
      <div class="signatories">
        <label>Approved By:</label>
        <div>
          <img src="storage/esign/approver.png" alt="Unavailable">
          <p>Elena Cruz</p>
          <p>President</p>
        </div>
      </div>
    </div>

    <span>
      &ldquo;Disclosing these confidential records to unauthorized personnel is punishable with Termination under Code of Discipline Section IV
      No. 4.15 Betrayal of company&rsquo;s trust and confidence Unauthorized disclosure of restricted company information such as but not limited to
      development plans, budgets, details of finances and marketing strategies, test questionnaires and records, voluntarily and willingly to outsiders,
      competitors and/or those who are not authorized to possess such information.&rdquo;
    </span>
  </div>
</div>

<!-- PAGE 2 : FOR 201 FILING -->
<div class="page page-1 outer-border p-[3px] relative">
  <div class="inner-border px-3 py-5 flex flex-col items-center">
    <div class="copy absolute text-lg font-bold text-gray-400 tracking-widest">
      <p>FOR 201 FILING</p>
    </div>

    <div class="print-header">
      <img src="{{asset('images/BGC.png')}}" alt="">
      <h3 class="font-courier">
        BROOKSIDE FARMS CORPORATION <br>
        Anupul, Bamban, Tarlac
      </h3>
      <h1 class="font-courier">NOTICE OF PERSONNEL ACTION</h1>
      <h2 class="font-courier text-[#70ad47]">WAGE ORDER NO. 24</h2>
    </div>

    <table>
      <tr>
        <td>Name: Juan Dela Cruz</td>
        <td>Employee No: 2024-00123</td>
      </tr>
      <tr>
        <td>Date Hired: 03/15/2021</td>
        <td>Division: Production</td>
      </tr>
      <tr>
        <td>Employment Status: Regular</td>
        <td>Date of Effectivity: 08/01/2026</td>
      </tr>
    </table>

    <table class="text-center">
      <tr>
        <td class="!bg-[#e2efd9]">FROM</td>
        <td class="!bg-[#e2efd9]">ACTION REFERENCE</td>
        <td class="!bg-[#e2efd9]">TO</td>
      </tr>
      <tr>
        <td>Farm 1 - Anupul</td>
        <td class="!bg-[#e2efd9] capitalize">Place of Assignment</td>
        <td>Farm 3 - Bamban</td>
      </tr>
      <tr>
        <td>Maria Santos</td>
        <td class="!bg-[#e2efd9] capitalize">Immediate Head</td>
        <td>Pedro Reyes</td>
      </tr>
      <tr>
        <td>Level 3</td>
        <td class="!bg-[#e2efd9] capitalize">Job Level</td>
        <td>Level 4</td>
      </tr>
      <tr>
        <td>10 days</td>
        <td class="!bg-[#e2efd9] capitalize">Leave Credits</td>
        <td>15 days</td>
      </tr>
    </table>

    <table class="text-center">
      <tr><td class="!bg-[#e2efd9]">REMARKS AND OTHER CONSIDERATION</td></tr>
      <tr>
        <td>
          <div class="w-full text-center remarks-text" style="word-break: break-word; min-height: 1.5em;">
            Adjustment pursuant to Wage Order No. 24.<br>
            • Effective on the date indicated above.<br>
            • All other terms and conditions remain unchanged.
          </div>
        </td>
      </tr>
    </table>

    <table class="text-center">
      <tr><td class="!bg-[#e2efd9]">CONFIRMATION OF APPOINTMENT</td></tr>
      <tr>
        <td class="flex items-center justify-around">
          <div class="confirmation-field">(SIGNATURE OVER PRINTED NAME)</div>
          <div class="confirmation-field">(DATE RECEIVE)</div>
        </td>
      </tr>
    </table>

    <div class="grid grid-cols-4 w-full px-6">
      <div class="signatories">
        <label>Prepared By:</label>
        <div>
          <img src="storage/esign/hr.png" alt="Unavailable">
          <p>Ana Lopez</p>
          <p>HR Assistant</p>
        </div>
      </div>
      <div class="signatories">
        <label>Noted By:</label>
        <div>
          <img src="storage/esign/hra.png" alt="Unavailable">
          <p>Carlo Mendoza</p>
          <p>Head, Human Resources</p>
        </div>
      </div>
      <div class="signatories">
        <label>Recommended By:</label>
        <div>
          <img src="storage/esign/divisionhead.png" alt="">
          <p>Ramon Garcia</p>
          <p>Division Head</p>
        </div>
      </div>
      <div class="signatories">
        <label>Approved By:</label>
        <div>
          <img src="storage/esign/approver.png" alt="Unavailable">
          <p>Elena Cruz</p>
          <p>President</p>
        </div>
      </div>
    </div>

    <span>
      &ldquo;Disclosing these confidential records to unauthorized personnel is punishable with Termination under Code of Discipline Section IV
      No. 4.15 Betrayal of company&rsquo;s trust and confidence Unauthorized disclosure of restricted company information such as but not limited to
      development plans, budgets, details of finances and marketing strategies, test questionnaires and records, voluntarily and willingly to outsiders,
      competitors and/or those who are not authorized to possess such information.&rdquo;
    </span>
  </div>
</div>

<!-- PAGE 3 : PAYROLL COPY -->
<div class="page page-2 outer-border p-[3px] relative">
  <div class="inner-border px-3 py-5 flex flex-col items-center">
    <div class="copy absolute text-lg font-bold text-gray-400 tracking-widest">
      <p>PAYROLL COPY</p>
    </div>

    <div class="print-header">
      <img src="{{asset('images/BGC.png')}}" alt="">
      <h3 class="font-courier">
        BROOKSIDE FARMS CORPORATION <br>
        Anupul, Bamban, Tarlac
      </h3>
      <h1 class="font-courier">NOTICE OF PERSONNEL ACTION</h1>
      <h2 class="font-courier text-[#70ad47]">WAGE ORDER NO. 24</h2>
    </div>

    <table>
      <tr>
        <td>Name: Juan Dela Cruz</td>
        <td>Employee No: 2024-00123</td>
      </tr>
      <tr>
        <td>Date Hired: 03/15/2021</td>
        <td>Division: Production</td>
      </tr>
      <tr>
        <td>Employment Status: Regular</td>
        <td>Date of Effectivity: 08/01/2026</td>
      </tr>
    </table>

    <table class="text-center">
      <tr>
        <td class="!bg-[#e2efd9]">FROM</td>
        <td class="!bg-[#e2efd9]">ACTION REFERENCE</td>
        <td class="!bg-[#e2efd9]">TO</td>
      </tr>
      <tr>
        <td>Farm 1 - Anupul</td>
        <td class="!bg-[#e2efd9] capitalize">Place of Assignment</td>
        <td>Farm 3 - Bamban</td>
      </tr>
      <tr>
        <td>Maria Santos</td>
        <td class="!bg-[#e2efd9] capitalize">Immediate Head</td>
        <td>Pedro Reyes</td>
      </tr>
      <tr>
        <td>Level 3</td>
        <td class="!bg-[#e2efd9] capitalize">Job Level</td>
        <td>Level 4</td>
      </tr>
      <tr>
        <td>10 days</td>
        <td class="!bg-[#e2efd9] capitalize">Leave Credits</td>
        <td>15 days</td>
      </tr>
    </table>

    <table class="text-center">
      <tr><td class="!bg-[#e2efd9]">REMARKS AND OTHER CONSIDERATION</td></tr>
      <tr>
        <td>
          <div class="w-full text-center remarks-text" style="word-break: break-word; min-height: 1.5em;">
            Adjustment pursuant to Wage Order No. 24.<br>
            • Effective on the date indicated above.<br>
            • All other terms and conditions remain unchanged.
          </div>
        </td>
      </tr>
    </table>

    <table class="text-center">
      <tr><td class="!bg-[#e2efd9]">CONFIRMATION OF APPOINTMENT</td></tr>
      <tr>
        <td class="flex items-center justify-around">
          <div class="confirmation-field">(SIGNATURE OVER PRINTED NAME)</div>
          <div class="confirmation-field">(DATE RECEIVE)</div>
        </td>
      </tr>
    </table>

    <div class="grid grid-cols-4 w-full px-6">
      <div class="signatories">
        <label>Prepared By:</label>
        <div>
          <img src="storage/esign/hr.png" alt="Unavailable">
          <p>Ana Lopez</p>
          <p>HR Assistant</p>
        </div>
      </div>
      <div class="signatories">
        <label>Noted By:</label>
        <div>
          <img src="storage/esign/hra.png" alt="Unavailable">
          <p>Carlo Mendoza</p>
          <p>Head, Human Resources</p>
        </div>
      </div>
      <div class="signatories">
        <label>Recommended By:</label>
        <div>
          <img src="storage/esign/divisionhead.png" alt="">
          <p>Ramon Garcia</p>
          <p>Division Head</p>
        </div>
      </div>
      <div class="signatories">
        <label>Approved By:</label>
        <div>
          <img src="storage/esign/approver.png" alt="Unavailable">
          <p>Elena Cruz</p>
          <p>President</p>
        </div>
      </div>
    </div>

    <span>
      &ldquo;Disclosing these confidential records to unauthorized personnel is punishable with Termination under Code of Discipline Section IV
      No. 4.15 Betrayal of company&rsquo;s trust and confidence Unauthorized disclosure of restricted company information such as but not limited to
      development plans, budgets, details of finances and marketing strategies, test questionnaires and records, voluntarily and willingly to outsiders,
      competitors and/or those who are not authorized to possess such information.&rdquo;
    </span>
  </div>
</div>

<script>
const remarksLen = 148;

let cls = null;
if      (remarksLen > 250) cls = 'remarks-lg';
else if (remarksLen > 120) cls = 'remarks-md';
else if (remarksLen > 60)  cls = 'remarks-sm';

if (cls) {
    document.querySelectorAll('.remarks-text').forEach(el => el.classList.add(cls));
}
</script>

</body>
</html>