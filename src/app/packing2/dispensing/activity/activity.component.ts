import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-activity',
  templateUrl: './activity.component.html',
  styleUrls: ['./activity.component.css']
})
export class ActivityComponent implements OnInit {

  isView = false;
  results;
  selectedResult = [];
  selectedIndex = -1;
  isStart = false;
  today = '';
  selectedMaterial = [];
  available_ars = []
  containers = [];
  ars = [];
  balance_qty = 0;
  employees;
  gross_wt = 0;
  tare_wt = 0;
  lafs;
  gross_total = 0;
  tare_total = 0;
  net_total = 0;
  dispensing_room;
  employee;
  emp_id = '';
  balances;
  qcperson;
  officer;
  qaperson;
  ar_data = [];
  available_ars_data = [];
  selectedBalance = [];
  selectedLAF = [];
  start_time = '';
  end_time = '';
  start_date: Date;
  end_date: Date;
  pressure_reading = '';
  start_rlaf_time = '';
  rlaf_start;
  end_laf_time = '';
  isViewshow = false;
  selectedview = [];
  selectedContainer = [];
  operator;
  cleaning_from = '';
  cleaning_to = '';
  fifo_method = '';
  constructor(private service: DataAccessService) {
    // this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd')
  }

  ngOnInit(): void {
    this.getAcceptedRequests();
  }

  getAcceptedRequests() {
    this.service.get('store/dispensing.php?type=get_Dispensing_Requests_prod_checking_pk&material_type=Packing Material').subscribe(response => {
      this.results = response;
      if (this.results.length > 0) {
        if (this.selectedIndex !== -1) {
          this.selectedResult = this.results[this.selectedIndex];
          this.isView = true;
        } else {
          this.isStart = false;
          this.isView = false;
        }
      } else {
        this.isStart = false;
        this.isView = false;
      }
    });
  }

  view(index) {
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  calculate_qty_to_dispense(ar_qty, net_qty) {

    let qty_to_dispense = ((Number(this.balance_qty) * Number(ar_qty)) / Number(net_qty)).toFixed(2);
    return qty_to_dispense;
  }

  show(index) {
    let last_container_id = 0;
    this.fifo_method = this.selectedResult['fifo_method'];;
    let material = this.selectedResult['materials'];
    this.selectedMaterial = material[index];
    this.available_ars_data = this.selectedMaterial['available_ars'];
    this.available_ars = this.selectedMaterial['containers'];
    if (this.selectedMaterial['material_code'] == 'RM-017') {
      this.balance_qty = 326;
    } else {
      this.balance_qty = +this.selectedMaterial['batch_qty'];
    }





    this.isStart = true;
    this.isView = false;

    this.getStoreEmployees();
  }

  calc_tare_weight(value, idx) {
    if (Number(value) > Number(this.available_ars[idx]['net_weight'])) {
      alertify.error("Tare weight exceed than Net weight");
      return;
    }
    this.available_ars[idx]['gross_wt'] = Number(this.available_ars[idx]['net_weight']) + Number(value);
    this.tare_total = 0;
    this.gross_total = 0;
    this.net_total = 0;
    for (let i = 0; i <= this.available_ars.length; i++) {
      this.gross_total = (Number(this.gross_total) + Number(this.available_ars[i]['gross_wt']));
      this.tare_total = (Number(this.tare_total) + Number(this.available_ars[i]['tare_wt']));
      this.net_total = (Number(this.net_total) + Number(this.available_ars[i]['net_weight']));
    }
  }


  getStoreEmployees() {
    this.service.get('store/dispensing.php?type=getStoreEmployees').subscribe(response => {
      this.employees = response;
    });
  }
  addARdata(data) {
    if (!data.valid) {
      alertify.error("all fields are required");
      return;
    }
    let temp = data.value;
    if (+this.balance_qty > 0) {

    }
    //ar_data
  }

   

  viewshow(index) {
    let material = this.selectedResult['materials'];
    this.selectedMaterial = material[index];
    this.selectedContainer = this.selectedMaterial['containers'];
    console.log(this.selectedContainer);
    this.isViewshow = true;
  }

  pm_hdr_id;
  updateDispensingStatus(status) {

    let temp = {};
    this.service.post('store/dispensing.php?type=update_prod_dispence_status&pm_hdr_id=' + this.selectedMaterial['pm_hdr_id'] + '&status=' + status, null).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Dispensing Status Updated Successfully!');
        this.isStart = false;
        this.getAcceptedRequests();
        this.ars = [];
        this.containers = [];
        this.selectedMaterial = [];
        temp = [];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  print(data) {
    this.service.open('store/dispensing.php?type=printLabel&material_code=' + data + '&id=' + this.selectedResult['id']);
  }
}
