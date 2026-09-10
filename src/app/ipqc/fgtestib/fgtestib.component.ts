import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-fgtestib',
  templateUrl: './fgtestib.component.html',
  styleUrls: ['./fgtestib.component.css']
})
export class FgtestibComponent implements OnInit {

  results;
  isView = false;
  selectedResult = [];
  isDate = false;
  id;
  expected_start_date;
  expected_complete_date;
  selectedIndex = -1;
  supervisors;
  newSupervisor = false;
  form = false;
  newWorker= false;
  names;
  operators;
  operator_names;
  selectedPage = 0;
  constructor(private service: DataAccessService, private router: Router) {
 
    
   }

  ngOnInit() {
    this.getReadyBatchPlans();
    this.getparams();


  }
  

  getReadyBatchPlans() {
    this.service.get('production/product.php?type=fg_coa&material_type=Raw Material').subscribe(response => {
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.view(this.selectedIndex);
      }
    });
  }


  sample_qty;
  updatesample(status) {
    this.service.post('production/product.php?type=update_qc_sample_app&id=' + this.selectedResult['id']+'&status=' + status, JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Save Successfully');
        this.form = false;
        this.router.navigate(['/ipqc']);
       } else {
        alertify.error(response['status']);
      }
    });
  }


  getEmployees() {
    this.service.get('employee.php?type=getProductionExecutiveOfficers').subscribe(response => {
      this.supervisors = response;
    });
  }

  getOperators() {
    this.service.get('common.php?type=getLabours').subscribe(response => {
      this.operators = response;
    });
  }

  view(index) {
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.form = true;
  }

  downloadview(index){
    this.selectedResult = this.results[index];
    this.service.open('production/product.php?type=fgcoa&material_type=Raw Material&id='+this.selectedResult['id']);
  }

  isNewSupervisors(id) {
    this.id = id;
    this.newSupervisor = true;
  }

  isNewWorkers(id) {
    this.id = id;
    this.newWorker = true;
  }
 

  saveparam(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('production/product.php?type=saveCoaparams', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getparams();
        this.isTerm = false;
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alertify.error('Please try Again');
      }
    });
  }
  parms;
  getparams() {
    this.service.get('production/product.php?type=getparamssss').subscribe(response => {
      this.parms = response;
    });
  }
  isCoa =false;
  prepCoa(){




    const payload = {
      id: this.selectedResult['id'],
      lots_data: this.lots_data
    };

    console.log(payload);

    this.service.post('production/product.php?type=save_coa_checklist&id=' + this.selectedResult['id'], JSON.stringify(payload)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Successfully');
        this.form = false;
        this.router.navigate(['/ipqc']);
       } else {
        alertify.error(response['status']);
      }
    });


     

  }



  isTerm = false;
  isEdit = false;
  delTerm(id) {
    this.service.get('production/product.php?type=deleteCOATerm&id=' + id).subscribe(response => {
      this.getparams();
      if (response['status'] == 'success') {
        this.isEdit = false;
        alertify.success('Term Deleted Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  } 
 

  addStage(lot_form) {
    if (!lot_form.valid) {
      alertify.error('All fields are required');
      return;
    }

     

    const newLot = lot_form.value;
   

    this.lots_data.push(newLot);
    console.log(this.lots_data);
    lot_form.resetForm();

  }

 
  lots_data =[];
 
}
