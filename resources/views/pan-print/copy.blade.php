{{-- One printed copy of the PAN — included three times (Employee / 201 Filing / Payroll).
     Markup preserved from the hand-ported layout; only the data is live.
     Expects: $pan (PanRequest with employee/department/form/participants), $label. --}}
@php
  $form = $pan->form;
  $participants = [
      ['label' => 'Prepared By:', 'user' => $form->preparedBy],
      ['label' => 'Noted By:', 'user' => $pan->hrApprover],
      ['label' => 'Recommended By:', 'user' => $pan->divisionHead],
      ['label' => 'Approved By:', 'user' => $pan->finalApprover],
  ];
@endphp
  <div class="inner-border px-3 py-5 flex flex-col items-center">
    <div class="copy absolute text-lg font-bold text-gray-400 tracking-widest">
      <p>{{ $label }}</p>
    </div>

    <div class="print-header">
      <img src="{{ asset('images/BGC.png') }}" alt="">
      <h3 class="font-courier">
        BROOKSIDE FARMS CORPORATION <br>
        Anupul, Bamban, Tarlac
      </h3>
      <h1 class="font-courier">NOTICE OF PERSONNEL ACTION</h1>
      <h2 class="font-courier text-[#70ad47]">
        @if ($pan->action_type->requiresWageNumber() && $form->wage_no)
          WAGE ORDER NO. {{ strtoupper($form->wage_no) }}
        @else
          {{ strtoupper($pan->action_type->label()) }}
        @endif
      </h2>
    </div>

    <table>
      <tr>
        <td>Name: {{ $pan->employee->name }}</td>
        <td>Employee No: {{ $pan->employee->employee_no }}</td>
      </tr>
      <tr>
        <td>Date Hired: {{ $form->date_hired?->format('m/d/Y') ?? '—' }}</td>
        <td>Division: {{ $pan->employee->department->name }}</td>
      </tr>
      <tr>
        <td>Employment Status: {{ $form->employment_status?->label() ?? '—' }}</td>
        <td>Date of Effectivity: {{ $form->doe_from?->format('m/d/Y') ?? '—' }}</td>
      </tr>
    </table>

    <table class="text-center">
      <tr>
        <td class="!bg-[#e2efd9]">FROM</td>
        <td class="!bg-[#e2efd9]">ACTION REFERENCE</td>
        <td class="!bg-[#e2efd9]">TO</td>
      </tr>
      @foreach ($form->displayRows() as $row)
      <tr>
        <td>{{ $row['from'] }}</td>
        <td class="!bg-[#e2efd9] capitalize">{{ $row['label'] }}</td>
        <td>{{ $row['to'] }}</td>
      </tr>
      @endforeach
    </table>

    <table class="text-center">
      <tr><td class="!bg-[#e2efd9]">REMARKS AND OTHER CONSIDERATION</td></tr>
      <tr>
        <td>
          <div class="w-full text-center remarks-text" style="word-break: break-word; min-height: 1.5em;">
            {!! nl2br(e($form->remarks ?? '—')) !!}
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
      @foreach ($participants as $signatory)
      <div class="signatories">
        <label>{{ $signatory['label'] }}</label>
        <div>
          @if ($signatory['user']?->esign_path)
          <img src="{{ route('user.esign', $signatory['user']) }}" alt="Unavailable">
          @endif
          <p>{{ $signatory['user']?->name ?? '—' }}</p>
          <p>{{ $signatory['user']?->position ?? '' }}</p>
        </div>
      </div>
      @endforeach
    </div>

    <span>
      &ldquo;Disclosing these confidential records to unauthorized personnel is punishable with Termination under Code of Discipline Section IV
      No. 4.15 Betrayal of company&rsquo;s trust and confidence Unauthorized disclosure of restricted company information such as but not limited to
      development plans, budgets, details of finances and marketing strategies, test questionnaires and records, voluntarily and willingly to outsiders,
      competitors and/or those who are not authorized to possess such information.&rdquo;
    </span>
  </div>
