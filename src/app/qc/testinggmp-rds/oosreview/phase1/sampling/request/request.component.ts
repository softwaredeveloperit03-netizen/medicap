import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

declare let alertify;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {
  isView = false;
  results;

  selectedSampling = [];
  employees;
    emp_id: string;
    isDIGI: boolean=false
    materialForm: any;
    isbutton: boolean=true
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getSamplingRecords();
    this.getQcPersons()
  }

  getSamplingRecords() {
    this.service.get('qc/sampling.php?type=getPendingAllocation&for=oos').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isView = true;
    this.getQcPersons();
  }

  getQcPersons() {
    this.service.get('qc/sampling.php?type=getQcPersons').subscribe(response => {
      this.employees = response;
    });
  }

  allocatePerson(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedSampling['id'];
    temp['inword_no'] = this.selectedSampling['inword_no'];
    temp['grn_no'] = this.selectedSampling['grn_no'];
    temp['material_code'] = this.selectedSampling['material_code'];
    temp['material_name'] = this.selectedSampling['material_name'];
    temp['material_name'] = this.selectedSampling['material_name'];
    temp['urgency'] = this.selectedSampling['urgency'];
    this.service.post('qc/sampling.php?type=allocatePerson', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling Person Allocated Successfully');
        this.isView = false;
        this.getSamplingRecords();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  
  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.materialForm=value
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
        this.allocate(this.materialForm)
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }

  masterId ='master';
  allocate(formData) {
    // if (!formData.valid) {
    //   alertify.error('Sampling Person is Required!');
    //   return;
    // }
    let temp = formData.value;

    let data = [];
    for (let  i = 0; i < this.results.length; i++) {
      let result = this.results[i];
      if (result['check']) {
        result['sampling_person'] = temp['sampling_person'];
        result['micro_person'] = temp['micro_person'];
        result['alternate_qc_person'] = temp['alternate_qc_person'];
        result['alternate_micro_person'] = temp['alternate_micro_person'];
        result['newoos_id'] = result['newoos_id'];
        data[data.length] = result;
      }
    }
    this.service.post('qc/sampling.php?type=allocatePerson&for=oos', JSON.stringify(data)).subscribe(response => {
      // if (response['status'] == 'success') {
      //   alertify.success('Sampling Person Allocated Successfully!');
      //   this.getSamplingRecords();
      // } else {
      //   alertify.error('Failed: An error occured, Please try again!');
      // }
      if (response['status'] === 'success') {
        alertify.success('Sampling Person Allocated Successfully!');
        this.isbutton=true
        this.getSamplingRecords();
        // this.router.navigate(['/qc/sampling/raw/req_new']);

      } else {
        alertify.error(response['status']);
      }
    });
  }

}
