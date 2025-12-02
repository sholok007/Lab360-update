@extends('layout.app')
@section('title','Lab360::Brand List')
@section('content')

<style>
    
.dropdowns {
  margin-bottom: 20px;
}

.dropdowns select {
  padding: 5px 10px;
  margin: 0 10px;
  font-size: 16px;
}

.box-grid {
  display: grid;
  grid-template-columns: repeat(5, 100px);
  grid-gap: 40px;
  justify-content: center;
}

.box {
  width: 100px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #333;
  cursor: pointer;
  font-weight: bold;
  font-size: 16px;
  user-select: none;
  transition: background-color 0.3s;
  text-align: center;
  padding: 2px;
  
}

.box_model{
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #333;
  cursor: pointer;
  font-weight: bold;
  font-size: 18px;
  user-select: none;
  transition: background-color 0.3s;
}
.box-grid_model {
  display: grid;
  grid-template-columns: repeat(5, 60px);
  grid-gap: 10px;
  justify-content: center;
}

.box.checked {
  background-color: green;
  color: white;
}

.custom-modal-width {
    max-width: 800px; 
    width: 100%; 
}

.form-check-input[type="radio"] {
    width: 20px;       
    height: 20px;     
    cursor: pointer;
    margin-top: -2px!important;
}

.form-check-label {
    margin-left: 8px; 
    font-weight: 500;
    font-size: 16px;
    cursor: pointer;
    
}

.small {
    font-size: 40%;
}
</style>
<div class="container-fluid">
     <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                    <h1>Machine Setup</h1>
                   <div class="d-flex justify-content-center bd-highlight">
                      <div class="p-2 bd-highlight col-md-3">
                          <select class="form-control" id="chooseTest">
                              <option>Select Test</option>
                              @foreach($tests as $test)
                                  <option value="{{ $test->id }}">{{ $test->name }}</option>
                              @endforeach
                          </select>
                      </div>
                      <div class="p-2 bd-highlight col-md-3">
                          <select class="form-control" id="selectBrand">
                              <option>Select Brand</option>
                          </select>
                      </div>
                  </div>

                  <!-- Box Grid -->
                  <div class="d-flex justify-content-center bd-highlight mb-3">
                      <div class="p-2 bd-highlight">
                          <div class="box-grid mt-5" id="boxGrid">
                            @foreach($locations as $location)
                                <div class="box" id="box{{ $location->id }}" data-booked="0">
                                    {{ $location->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                  </div>
                  <!-- Submit button -->
                  <div class="d-flex justify-content-center bd-highlight mb-3">
                      <button id="finalSubmitBtn" type="button" class="btn btn-success" disabled>Submit</button>
                  </div>
                </div>
            </div>    
        </div>   
    </div>     
</div>


<!-- Modal Form -->
<div class="modal fade" id="addReagentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog custom-modal-width">
        <form id="assignReagentsForm">
            @csrf
            <input type="hidden" name="brand_id" id="modalBrandId">
            <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">Assign Reagents & Locations for <span id="modalBrandName"></span></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                  <div class="row">
                      <!-- Reagents Column -->
                      <div class="col-md-6">
                          <h6>Reagents</h6>
                          <div id="modalReagentsBody" class="mb-3">
                              <p class="text-center">Loading reagents...</p>
                          </div>
                      </div>

                      <!-- Locations Column -->
                      <div class="col-md-6">
                          <h6>Locations</h6>
                          <div id="modalLocationsBody" class="box-grid_model">
                                @foreach($locations as $location)
                                    <div class="form-check box_model">
                                        <input type="checkbox" class="form-check-input location-checkbox" 
                                               name="location_ids[]" value="{{ $location->id }}" 
                                               id="modalLoc{{ $location->id }}">
                                        <label class="form-check-label" for="modalLoc{{ $location->id }}">
                                            {{ $location->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                      </div>
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-success">Save</button>
              </div>
          </div>
        </form>
    </div>
</div>



@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialize modal
const modalEl = document.getElementById('addReagentModal');
const bsModal = new bootstrap.Modal(modalEl, {backdrop: 'static', keyboard: true});
const boxGrid = document.getElementById("boxGrid");
const finalSubmitBtn = document.getElementById('finalSubmitBtn');
// Test -> Brand AJAX
document.getElementById('chooseTest').addEventListener('change', function(){
    const testId = this.value;
    const brandSelect = document.getElementById('selectBrand');
    brandSelect.innerHTML = '<option value="">Select Brand</option>';
    if(testId){
        fetch(`/brands-by-test/${testId}`)
        .then(res => res.json())
        .then(data => {
            data.forEach(brand => {
                const option = document.createElement('option');
                option.value = brand.id;
                option.text = brand.name;
                brandSelect.appendChild(option);
            });
        });
    }
});

// Brand select -> modal with reagents
document.getElementById('selectBrand').addEventListener('change', function(){
    const brandId = this.value;
    if(!brandId) return;

    const modalBrandId = document.getElementById('modalBrandId');
    const modalBrandName = document.getElementById('modalBrandName');
    const modalReagentsBody = document.getElementById('modalReagentsBody');

    modalBrandId.value = brandId;
    modalBrandName.textContent = this.options[this.selectedIndex].text;
    modalReagentsBody.innerHTML = '<p class="text-center">Loading reagents...</p>';

    fetch(`/brands/${brandId}/reagents`)
    .then(res => res.json())
    .then(data => {
        const reagents = data.reagents;
        if(reagents.length === 0){
            modalReagentsBody.innerHTML = '<p class="text-muted text-center">No reagents found for this brand.</p>';
            return;
        }

        const fragment = document.createDocumentFragment();
        reagents.forEach(r => {
            const div = document.createElement('div');
            div.classList.add('form-check', 'mb-2');

            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'reagent_id';
            input.value = r.id;
            input.classList.add('form-check-input');

            // Enable deselect on click
            input.addEventListener('click', function() {
                if(this.previousChecked){
                    this.checked = false;
                    this.previousChecked = false;
                } else {
                    const allRadios = document.querySelectorAll('input[name="reagent_id"]');
                    allRadios.forEach(radio => radio.previousChecked = false);
                    this.previousChecked = this.checked;
                }
            });

            const label = document.createElement('label');
            label.classList.add('form-check-label');
            label.textContent = r.name;

            div.appendChild(input);
            div.appendChild(label);
            fragment.appendChild(div);
        });

        modalReagentsBody.innerHTML = '';
        modalReagentsBody.appendChild(fragment);
    });

    bsModal.show();
});


// Modal form submit
document.getElementById('assignReagentsForm').addEventListener('submit', function(e){
    e.preventDefault();

    const testId = document.getElementById('chooseTest').value;
    const testName = document.getElementById('chooseTest').selectedOptions[0].text;
    const brandId = document.getElementById('modalBrandId').value;
    const brandName = document.getElementById('modalBrandName').textContent;

    const selectedReagent = document.querySelector('input[name="reagent_id"]:checked');
    if(!selectedReagent){
        alert("Please select a reagent!");
        return;
    }
    const reagentId = selectedReagent.value;
    const reagentName = selectedReagent.nextSibling.textContent || selectedReagent.parentNode.textContent;

    const selectedLocations = Array.from(document.querySelectorAll('.location-checkbox:checked'));
    if(selectedLocations.length === 0){
        alert("Please select at least one location!");
        return;
    }

    // Render into boxes
    selectedLocations.forEach(loc => {
        const locId = loc.value;
        const box = document.getElementById('box' + locId);
        if(box.dataset.booked === "1") return;

        box.dataset.booked = "1";
        box.style.backgroundColor = "#04361aff";

        const locLabel = loc.nextElementSibling;
        const locName = locLabel ? locLabel.textContent : locId;

        box.innerHTML = `
            <div style="display:flex; flex-direction: column; gap:2px; font-size:12px;">
                <small>${locName}</small>
                <small>Test: ${testName}</small>
                <small>Brand: ${brandName}</small>
                <small>Reagent: ${reagentName}</small>
            </div>
        `;

        // disable modal checkbox
        loc.disabled = true;
        loc.checked = false;
    });

    // Enable final submit button
    finalSubmitBtn.disabled = false;

    bsModal.hide();
});


</script>


@endsection