import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { finalize } from 'rxjs/operators';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

 
  ngOnInit() {
    this.getPendingTestingForms();
    this.emp_id = localStorage.getItem('emp_id');
   }
 

  emp_id = localStorage.getItem('emp_id');

  results: any[] = [];
  material_type = 'Raw Material';
  getPendingTestingForms() {
    this.service.get('qc/testing/raw.php?type=getTestingFormsForChecking&material_type='+this.material_type).subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
    });
  }
 

  isTestView = false; 
  isView = false; 
  selectedTesting = {};
  tests;
 
  viewTesting(data) {
    this.loading = true;
    this.loadingMessage = 'Please Wait Tests Are Loading......';
    setTimeout(() => {
      this.getTestByTestingNO(data['testing_no']);
    }, 300);
    this.selectedTesting = data;
    this.isView = true;
    this.isTestView = false;
  }
 
  loading = false;
  loadingMessage = '';
  
  getTestByTestingNO(testing_no: string) {
    this.service
      .get(
        'qc/testing/raw.php?type=getTestByTestingNO&testing_no=' +
          encodeURIComponent(testing_no)
      )
      .pipe(
        finalize(() => {
          this.loading = false;   // ALWAYS stop loader
        })
      ).subscribe({
        next: (response) => {
          this.tests = Array.isArray(response) ? response : [];
          this.allNotChecked = this.tests.length > 0 && this.tests.every(test => test.status === 'Checked');
        },
        error: (err) => {
          console.error('API error:', err);
        }
      });
  }

  allNotChecked = false;

  selectedTest = {};

  viewTest(data){
    data['performUSer'] = this.resolvePerformUser(data);
    this.selectedTest = data;
    this.checkingRemark = data['checkingRemark'] || '';

    this.isView = false;
    this.isTestView = true;
  }

  private resolvePerformUser(data: Record<string, unknown>): string {
    const performByName = String(data['performByName'] || '').trim();
    const performBy = String(data['performBy'] || '').trim();
    if (performByName || performBy) {
      return `${performByName}${performBy ? ' - ' + performBy : ''}`.trim();
    }
    const personName = String(data['personName'] || '').trim();
    const person = String(data['person'] || '').trim();
    if (personName || person) {
      return `${personName}${person ? ' - ' + person : ''}`.trim();
    }
    const personAltName = String(data['person_altName'] || '').trim();
    const personAlt = String(data['person_alt'] || '').trim();
    if (personAltName || personAlt) {
      return `${personAltName}${personAlt ? ' - ' + personAlt : ''}`.trim();
    }
    return '-';
  }
  

 
  isDIGI = false;
  temp  = {};

  openDigiSign(status){
    this.temp['testingId'] = this.selectedTesting['id'];
    this.temp['status'] = status
    this.isDIGI = true;
  }
 

  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    let loginPassward = data.value?.loginPassward;

    this.service.get('login.php?type=checkDigiSIgn&mpin=' + loginPassward +'&emp_id=' + localStorage.getItem('emp_id')).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        data.reset();
        this.SendTestingForApproval();
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
 
 
  SendTestingForApproval(){
    this.service.post('qc/testing/raw.php?type=SendTestingForApproval',JSON.stringify(this.temp)).subscribe(response => {
      if (response['status']) {
        alertify.success('Test Result Saved Successfully......');
        this.isView = false;
        this.isTestView = false;
        this.getPendingTestingForms();
        this.temp = {};
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }




  checkingRemark = '';
  checkTest(status){

    if (this.checkingRemark == '') {
      alert('Please Enter Checking Remark!!!!');
      return;
    }

    let temp = {};
    temp['status'] = status;
    temp['checkingRemark'] = this.checkingRemark;
    temp['selectedTestId'] = this.selectedTest['id'];

    this.service.post('qc/testing/raw.php?type=checkTest',JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alertify.success('Test Result Saved Successfully......');

        this.isView = true;
        this.isTestView = false;
        this.loading = true;
        this.loadingMessage = 'Please Wait Tests Are Loading......';
        setTimeout(() => {
          this.getTestByTestingNO(this.selectedTesting['testing_no']);
        }, 300);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

 
    searchQuery;
 
    get filteredMaterials(): any[] {
      if (!Array.isArray(this.results)) {
        return [];
      }
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
