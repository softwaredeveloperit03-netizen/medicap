import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-batch',
  templateUrl: './batch.component.html',
  styleUrls: ['./batch.component.css']
})
export class BatchComponent implements OnInit {
  products;
  results;
  isNew = false;
  batch_no = '';
  selectedProduct=[];
  intervals = 0;
  total_intervals = 0;
  isSelect = false;
  equipments;

  conditions= [
    {'id': 1,'condition': 'Long Term', 'intervals': '0, 3, 6, 9, 12, 24, 36, 48', 'total_intervals': '8', 'temperature': '25ºC±2ºC/60%±5%RH', 'selected': false, 'sample_qty': 0, 'interval': []},
    {'id': 2,'condition': 'Accelerated Stability', 'intervals': '0, 3, 6', 'total_intervals': '3', 'temperature': '40ºC±2ºC/75%±5%RH', 'selected': false, 'sample_qty': 0, 'interval': []},
    {'id': 3,'condition': 'Intermediate', 'intervals': '0, 3, 6, 9, 12', 'total_intervals': '5', 'temperature': '30ºC±2ºC/60%±5%RH', 'selected': false, 'sample_qty': 0, 'interval': []},
    {'id': 4,'condition': 'Zone IV - A', 'intervals': '0, 3, 6, 9, 12, 24, 36', 'total_intervals': '7', 'temperature': '', 'selected': false, 'sample_qty': 0, 'interval': []},
    {'id': 5,'condition': 'Zone IV - B', 'intervals': '0, 3, 6, 9, 12, 24, 36, 48', 'total_intervals': '8', 'temperature': '', 'selected': false, 'sample_qty': 0, 'interval': []},
    {'id': 6,'condition': 'Force Degradation', 'intervals': '0', 'total_intervals': '1', 'temperature': '', 'selected': false, 'sample_qty': 0, 'interval': []},
    {'id': 7,'condition': 'Special Study', 'intervals': '0', 'total_intervals': '1', 'temperature': '', 'selected': false, 'sample_qty': 0, 'interval': []},
    {'id': 7,'condition': 'Annual Stability', 'intervals': '0, 12, 24, 36', 'total_intervals': '4', 'temperature': '25ºC±2ºC/60%±5%RH', 'selected': false, 'sample_qty': 0, 'interval': []},
  ];

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingBatchNos();
  }

  getPendingBatchNos(){
    this.service.get('stability.php?type=getStabilityProduction').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }

  allocate(status) {
    this.service.post('production/plan.php?type=allocateBatchNo&batch_no=' + this.batch_no + '&id=' + this.selectedResult['id'] + '&status=' + status,JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Medicap Lot No Allocated Successfully');
        this.getPendingBatchNos();
        this.isNew = false;
      } else {
        alert(response['status']);
      }
    });
  }

  selectproduct(index) {
    index = index - 1;
    this.selectedProduct = this.products[index];
    this.isSelect = true;
  }





  updateCondition(index, event) {
    this.intervals = 0;
    this.conditions[index].selected = event.target.checked;
    let qty = +this.selectedProduct['sample_qty'] * 3 * +this.conditions[index].total_intervals;
    this.conditions[index].sample_qty = qty;
    let date = new Date();
    let interval0 = date;
    let temp0 = {};
    temp0['interval'] = "0";
    temp0['date'] = interval0.getFullYear() + "-" + (interval0.getMonth()+1) + "-" + interval0.getDate();
    temp0['singal_analysis'] = this.selectedProduct['sample_qty'];
    temp0['status'] = 'pending';

    date = new Date();
    let interval3 = new Date(date.setMonth(date.getMonth()+3));
    let temp3 = {};
    temp3['interval'] = "3";
    temp3['date'] = interval3.getFullYear() + "-" + (interval3.getMonth()+1) + "-" + interval3.getDate();
    temp3['singal_analysis'] = this.selectedProduct['sample_qty'];
    temp3['status'] = 'pending';

    date = new Date();
    let interval6 = new Date(date.setMonth(date.getMonth()+6));
    let temp6 = {};
    temp6['interval'] = "6";
    temp6['date'] = interval6.getFullYear() + "-" + (interval6.getMonth()+1) + "-" + interval6.getDate();
    temp6['singal_analysis'] = this.selectedProduct['sample_qty'];
    temp6['status'] = 'pending';

    date = new Date();
    let interval9 = new Date(date.setMonth(date.getMonth()+9));
    let temp9 = {};
    temp9['interval'] = "9";
    temp9['date'] = interval9.getFullYear() + "-" + (interval9.getMonth()+1) + "-" + interval9.getDate();
    temp9['singal_analysis'] = this.selectedProduct['sample_qty'];
    temp9['status'] = 'pending';

    date = new Date();
    let interval12 = new Date(date.setMonth(date.getMonth()+12));
    let temp12 = {};
    temp12['interval'] = "12";
    temp12['date'] = interval12.getFullYear() + "-" + (interval12.getMonth()+1) + "-" + interval12.getDate();
    temp12['singal_analysis'] = this.selectedProduct['sample_qty'];
    temp12['status'] = 'pending';

    date = new Date();
    let interval24 = new Date(date.setMonth(date.getMonth()+24));
    let temp24 = {};
    temp24['interval'] = "24";
    temp24['date'] = interval24.getFullYear() + "-" + (interval24.getMonth()+1) + "-" + interval24.getDate();
    temp24['singal_analysis'] = this.selectedProduct['sample_qty'];
    temp24['status'] = 'pending';

    date = new Date();
    let interval36 = new Date(date.setMonth(date.getMonth()+36));
    let temp36 = {};
    temp36['interval'] = "36";
    temp36['date'] = interval36.getFullYear() + "-" + (interval36.getMonth()+1) + "-" + interval36.getDate();
    temp36['singal_analysis'] = this.selectedProduct['sample_qty'];
    temp36['status'] = 'pending';

    date = new Date();
    let interval48 = new Date(date.setMonth(date.getMonth()+48));
    let temp48 = {};
    temp48['interval'] = "48";
    temp48['date'] = interval48.getFullYear() + "-" + (interval48.getMonth()+1) + "-" + interval48.getDate();
    temp48['singal_analysis'] = this.selectedProduct['sample_qty'];
    temp48['status'] = 'pending';

    let interval = [];
    if (1 == this.conditions[index].id) {
      interval[interval.length] = temp0;
      interval[interval.length] = temp3;
      interval[interval.length] = temp6;
      interval[interval.length] = temp9;
      interval[interval.length] = temp12;
      interval[interval.length] = temp24;
      interval[interval.length] = temp36;
      interval[interval.length] = temp48;
    } else if (2 == this.conditions[index].id) {
      interval[interval.length] = temp0;
      interval[interval.length] = temp3;
      interval[interval.length] = temp6;
    } else if (3 == this.conditions[index].id) {
      interval[interval.length] = temp0;
      interval[interval.length] = temp3;
      interval[interval.length] = temp6;
      interval[interval.length] = temp9;
      interval[interval.length] = temp12;
    } else if (4 == this.conditions[index].id) {
      interval[interval.length] = temp0;
      interval[interval.length] = temp3;
      interval[interval.length] = temp6;
      interval[interval.length] = temp9;
      interval[interval.length] = temp12;
      interval[interval.length] = temp24;
      interval[interval.length] = temp36;
    } else if (5 == this.conditions[index].id) {
      interval[interval.length] = temp0;
      interval[interval.length] = temp3;
      interval[interval.length] = temp6;
      interval[interval.length] = temp9;
      interval[interval.length] = temp12;
      interval[interval.length] = temp24;
      interval[interval.length] = temp36;
      interval[interval.length] = temp48;
    } else if (6 == this.conditions[index].id) {
      interval[interval.length] = temp0;
    } else if (7 == this.conditions[index].id) {
      interval[interval.length] = temp0;
    }
    this.conditions[index].interval = interval;
  }



  updateStabilityProtocol(action) {
    this.service.get('stability.php?type=updateStabilityProtocol&action=' + action + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        this.isNew = false;
        this.getPendingBatchNos();
      }
    });
  }




}
