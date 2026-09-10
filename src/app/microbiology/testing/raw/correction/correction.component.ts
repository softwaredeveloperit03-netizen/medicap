import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { finalize } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
  
@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css']
})
export class CorrectionComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }


  emp_id = localStorage.getItem('emp_id');
 
  ngOnInit() {
    this.getPendingTestForCorrection();
    this.emp_id = localStorage.getItem('emp_id');
   }
 

  results;
  material_type = 'Raw Material';
  getPendingTestForCorrection() {
    this.service.get('qc/testing/raw.php?type=getPendingMicrobiologyTestForCorrection&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
 





  selectedTest = {};
  isView = false;

  viewTest(data){
    this.selectedTest = data;
    this.isView = true;
  }
  

  
  reason = '';
 
  startTime!: string;
  endTime!: string;

  setTime(type: 'Start' | 'End'){
    const now = new Date();

    const year = now.getFullYear();
    const month = this.pad(now.getMonth() + 1); // months are 0-based
    const day = this.pad(now.getDate());
    const hours = this.pad(now.getHours());
    const minutes = this.pad(now.getMinutes());

    const localDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;

    if (type === 'Start') {
      this.startTime = localDateTime;
    } else {
      this.endTime = localDateTime;
    }
  }

  private pad(value: number): string {
    return value < 10 ? '0' + value : value.toString();
  }
  
 
  temp = {};
  isDIGI = false;
  form;

  openDigiSign(data){

    if (!data.valid) {
      alert('All Field Required!!!!');
      return;
    }
    this.form = data;
    this.temp = data.value;
    this.temp['observation'] = this.observation;
    if(this.temp['reason']=='Other'){
      this.temp['reason'] = this.temp['ifReasoneIsOther'];
    }
    this.temp['reason'] = this.reason;
    this.temp['selectedTestId'] = this.selectedTest['id'];
    this.temp['testing_no'] = this.selectedTest['testing_no'];
    this.temp['testFor'] = this.selectedTest['testFor'];
    this.temp['test_type'] = this.selectedTest['test_type'];
    this.temp['spec_test_id'] = this.selectedTest['spec_test_id'];
    this.temp['test'] = this.selectedTest['test'];
    this.temp['subtest'] = this.selectedTest['subtest'];
    this.temp['limit_type'] = this.selectedTest['limit_type'];
    this.temp['limits'] = this.selectedTest['limits'];
    this.temp['lower_limit'] = this.selectedTest['lower_limit'];
    this.temp['upper_limit'] = this.selectedTest['upper_limit'];
    this.temp['unit'] = this.selectedTest['unit'];
    this.temp['isoutside'] = this.selectedTest['isoutside'];
    this.temp['person'] = this.selectedTest['person'];
    this.temp['person_alt'] = this.selectedTest['person_alt'];
    this.temp['lab_no'] = this.selectedTest['lab_no'];
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
        this.updateTEstResultFromCorrection();
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
 
  updateTEstResultFromCorrection(){
    this.service.post('qc/testing/raw.php?type=updateTEstResultFromCorrection',JSON.stringify(this.temp)).subscribe(response => {
      if (response['status']) {
        alertify.success('Test Result Updated Successfully......');
        this.getPendingTestForCorrection();
        this.temp ={};
        this.form.reset();
        this.observation = '';
        this.isView = false
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }


  observation = '';


































    isMethodView = false;
    isProcedure = false;
    isEquipment = false;
    isChecmical = false;



 
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
