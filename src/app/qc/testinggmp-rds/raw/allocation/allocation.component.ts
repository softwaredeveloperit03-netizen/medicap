import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {
  isView = false;
  isLoadingTests = false;
 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingAllocationTestings();
    this.getQcChecmist();
    this.getMicrobiologist();
    this.getLabsLog();
  }
  

  results;
  material_type = 'Raw Material';
  getPendingAllocationTestings() {
    this.service.get('qc/testing/raw.php?type=getPendingAllocationTestings&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }

  employees;
  getQcChecmist() {
    this.service.get('qc/testing/raw.php?type=getQcChecmist').subscribe(response => {
      this.employees = response;
    });
  }

  microEmployees;
  getMicrobiologist() {
    this.service.get('qc/testing/raw.php?type=getMicrobiologist').subscribe(response => {
      this.microEmployees = response;
    });
  }

  labs;
  getLabsLog() {
    this.service.get('qc/lab.php?type=getLabsLog').subscribe(response => {
      this.labs = response;
    });
  }
 
  selectedTesting = {};
  view(data) {
    if(data['specification_no'] == 'NA'){
      alertify.error("Specification Is Not Added!!!!!!!!!!!!!");
      return;
    }
    this.selectedTesting = { ...data, spec_tests: [] };
    this.isView = true;
    this.loadSpecificationTests(data);
  }

  /** Same source as master/specification/raw/specification-logs → Add Specification. */
  private loadSpecificationTests(data: Record<string, unknown>): void {
    const specNo = String(data['specification_no'] || '').trim();
    if (!specNo || specNo === 'NA') {
      return;
    }
    const specId = data['spec_id'] != null ? String(data['spec_id']).trim() : '';
    let url =
      'qc/specification/raw.php?type=getSpecificationWithDetailsForDraft&specification_no=' +
      encodeURIComponent(specNo);
    if (specId) {
      url += '&spec_id=' + encodeURIComponent(specId);
    }
    this.isLoadingTests = true;
    this.service.get(url).subscribe({
      next: (response: Record<string, unknown>) => {
        const spectTests = Array.isArray(response['spectTests']) ? response['spectTests'] : [];
        this.selectedTesting = {
          ...this.selectedTesting,
          spec_tests: spectTests.map((t: Record<string, unknown>) => ({
            ...t,
            person: t['person'] || '',
            person_alt: t['person_alt'] || '',
            lab_no: t['lab_no'] || '',
            isoutside: t['isoutside'] || 'No',
          })),
        };
        this.isLoadingTests = false;
        if (!spectTests.length) {
          alertify.warning('No tests found in specification.');
        }
      },
      error: () => {
        this.isLoadingTests = false;
        alertify.error('Could not load specification tests.');
      },
    });
  }

  micro_status = 'Checked';
  outside_status = 'Checked';

  saveTestingPersonAllocation(data) {

    if(!data.valid){
      alertify.error("Please Add Remark....");
      return;
    }

  const tests = this.selectedTesting['spec_tests'];
  if (!Array.isArray(tests) || !tests.length) {
    alertify.error('Specification tests are not loaded.');
    return;
  }

  this.micro_status = 'Checked';
  this.outside_status = 'Checked';

  for (let i = 0; i < tests.length; i++) {
    if(tests[i].isoutside == 'Yes' && tests[i].test_type == 'Microbiology'){
      this.outside_status = 'Allocated';
    }
    if(tests[i].test_type == 'Microbiology' && tests[i].isoutside != 'Yes'){
      this.micro_status = 'Allocated';
    }
    if(tests[i].isoutside == 'Yes'){
      this.outside_status = 'Allocated';
    }
  }

    let temp = data.value;
    temp['id'] = this.selectedTesting['id'];
    temp['specification_no'] = this.selectedTesting['specification_no'];
    temp['testing_no'] = this.selectedTesting['testing_no'];
    temp['spec_tests'] =  this.selectedTesting['spec_tests']; 
    temp['micro_status'] =  this.micro_status; 
    temp['outside_status'] =  this.outside_status;
   
    this.service.post('qc/testing/raw.php?type=saveTestingPersonAllocation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getPendingAllocationTestings();
      }else{
        alertify.error("some error Ocuured");
      }
    });
  }

 
   searchQuery;
 
   get filteredMaterials(): any[] {
     if (!this.searchQuery || this.searchQuery.trim() === '') {
       return this.results; // If search query is empty or whitespace, return all materials
     }
 
     const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
     return this.results.filter((material) => {
       // Check if any field of the material contains the search query
       return Object.entries(material).some(([key, value]) => {
         if (key === 'entry_date') {
           // Convert the value to a Date object if it's not already
           const dateValue = typeof value === 'string' ? new Date(value) : value;
           // Check if the date value is valid and includes the search query
           return (
             dateValue instanceof Date &&
             dateValue.toISOString().slice(0, 10).includes(query)
           );
         } else {
           // Convert field value to lowercase and check if it includes the search query
           return value && value.toString().toLowerCase().includes(query);
         }
       });
     });
   }



}
