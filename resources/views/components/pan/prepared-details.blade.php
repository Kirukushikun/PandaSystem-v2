{{-- The "PAN Details — prepared by HR" extension, appended below x-pan.request-details in any
     role's Show view once a prepared PAN exists. Renders inside the same .card.
     `rows` mirrors the planned action_reference JSON: [{label, from, to, chg?, num?}, …] —
     chg highlights the To value green, num right-aligns money. --}}
@props([
    'preparedBy', 'dateHired', 'employmentStatus', 'effectivity',
    'wageNo' => null,
    'hrApprovedBy' => null,
    'refHeading' => null,
    'rows' => [],
    'remarks' => null,
])
<div class="sect">PAN Details — prepared by HR</div>
<div class="formgrid" style="padding-top:10px">
  <div class="field"><label>Prepared by</label><input readonly value="{{ $preparedBy }}"></div>
  <div class="field"><label>Date Hired</label><input readonly value="{{ $dateHired }}"></div>
  <div class="field"><label>Employment Status</label><input readonly value="{{ $employmentStatus }}"></div>
  <div class="field"><label>Effectivity</label><input readonly value="{{ $effectivity }}"></div>
  @if ($wageNo !== null)
  <div class="field"><label>Wage Order No.</label><input readonly value="{{ $wageNo }}"><span class="hint">Shown only for Wage Order actions</span></div>
  @endif
  @if ($hrApprovedBy !== null)
  <div class="field"><label>HR approved by</label><input readonly value="{{ $hrApprovedBy }}"></div>
  @endif
</div>
@if ($refHeading !== null)<div class="sect">{{ $refHeading }}</div>@endif
<div class="twrap"><table class="fromto" style="min-width:620px">
  <thead><tr><th style="width:200px">Item</th><th>From</th><th class="arrow"></th><th>To</th></tr></thead>
  <tbody>
    @foreach ($rows as $row)
    <tr>
      <td class="lbl">{{ $row['label'] }}</td>
      <td @if ($row['num'] ?? false) class="num" @endif>{{ $row['from'] }}</td>
      <td class="arrow">→</td>
      <td @if ($row['num'] ?? false) class="num" @endif>
        @if ($row['chg'] ?? false)<span class="chg">{{ $row['to'] }}</span>@else{{ $row['to'] }}@endif
      </td>
    </tr>
    @endforeach
  </tbody>
</table></div>
@if ($remarks !== null)
<div class="formgrid" style="padding-top:12px">
  <div class="field full"><label>Remarks</label>
    <textarea rows="2" readonly>{{ $remarks }}</textarea></div>
</div>
@endif
