import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-pm-planing',
  templateUrl: './pm-planing.component.html',
  styleUrls: ['./pm-planing.component.css'],
})
export class PmPlaningComponent implements OnInit {
  products;
  results;
  isNew = false;
  batch_no = '';
  materials = [];
  work_order_raw_materials = [];
  work_order_packing_materials = [];
  selectedResult = [];
  work_order_lots = [];
  pack_sizes = [];
  person;
  material_type = 'RM';
  emp_id: string;
  isDIGI: boolean;
  status: any;
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getPendingBatchNos();
    this.getQAPerson();
  }
  getQAPerson() {
    this.service.get('employee.php?type=getQAPersons').subscribe((response) => {
      this.person = response;
    });
  }
  getPendingBatchNos() {
    this.service
      .get(
        'production/workorder.php?type=get_pm_approved_work_orders_for_qa_approval'
      )
      .subscribe((response) => {
        this.results = response;
      });
    // this.service.get('production/plan.php?type=getPendingBatchNos').subscribe(response => {
    //   this.results = response;
    // });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.material_type = this.selectedResult['material_type'];
    this.materials = this.results[index]['materials'];
    this.pack_sizes = this.selectedResult['pack_sizes'];
    this.work_order_lots = this.results[index]['lots'];
    this.work_order_raw_materials = [];
    this.work_order_packing_materials = [];

    this.isNew = true;
  }

  allocate(status) {
    let obj = {
      id: this.selectedResult['id'],
      status: status,
    };
    this.service
      .post(
        'production/workorder.php?type=update_qa_status',
        JSON.stringify(obj)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Status has been saved successfully');
          this.getPendingBatchNos();
          this.isNew = false;
        } else {
          alertify.error('Failed: ' + response['status']);
        }
      });
    // this.service.post('production/plan.php?type=allocateBatchNo&batch_no=' + this.batch_no + '&id=' + this.selectedResult['id'] + '&status=' + status,JSON.stringify(this.selectedResult)).subscribe(response => {
    //   if (response['status'] == 'success') {
    //     alert('Medicap Lot No Allocated Successfully');
    //     this.getPendingBatchNos();
    //     this.isNew = false;
    //   } else {
    //     alert(response['status']);
    //   }
    // });
  }

  openDigiSign(value) {
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status = value;
  }

  loginPassward = '';
  digiSign(data) {
    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }

    this.service
      .get(
        'login.php?type=checkDigiSIgn&mpin=' +
          this.loginPassward +
          '&emp_id=' +
          this.emp_id
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Digi-Sign Verified successfully');
          this.isDIGI = false;
          this.loginPassward = '';
          this.allocate(this.status)
        } else {
          alertify.error('Digi-Sign Not Verified');
        }
      });
  }
}
