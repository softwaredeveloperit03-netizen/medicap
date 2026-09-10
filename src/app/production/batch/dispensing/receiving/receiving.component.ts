import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-receiving',
  templateUrl: './receiving.component.html',
  styleUrls: ['./receiving.component.css'],
  providers: [DatePipe]
})
export class ReceivingComponent implements OnInit {
  isView = false;
  qa_checking = 'Yes';
  prod_checking = 'Yes';
  dispensing_completed = 'No';
  isDispensingCompleted = false;
  results;
  selectedResult = [];
  selectedIndex = -1;
  isStart = false;
  isViewDispensing = false;
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
  remarks = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd')
  }

  ngOnInit(): void {
    this.getAcceptedRequests();
  }

  getAcceptedRequests() {
    this.service.get('production/workorder.php?type=get_dispensing_complted_requests_by_store&material_type=Raw Material').subscribe(response => {
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
    this.isDispensingCompleted = this.selectedResult['rm_received_by'].length > 0;
    let material = this.selectedResult['materials'];

    let dispensingCompleted = 'Yes';
    for (let i = 0; i < material.length; i++) {
      if (material[i]['checked_by'].length == 0) {
        console.log(JSON.stringify(material[i]));
        dispensingCompleted = 'No';
      }else{
        if(material[i]['dispense_recd_status']=='Reject'){
          dispensingCompleted = 'No';
        }
      }
    }
    this.dispensing_completed = dispensingCompleted;
    this.isView = true;
    console.log(this.isDispensingCompleted)
    console.log(this.dispensing_completed)
  }

  calculate_qty_to_dispense(ar_qty, net_qty) {

    let qty_to_dispense = ((Number(this.balance_qty) * Number(ar_qty)) / Number(net_qty)).toFixed(2);
    return qty_to_dispense;
  }

  show(index) {
    this.available_ars_data = []
    let last_container_id = 0;
    this.fifo_method = this.selectedResult['fifo_method'];;
    let material = this.selectedResult['materials'];


    this.selectedMaterial = material[index];
    this.available_ars_data = this.selectedMaterial['available_ars'];
    if (this.selectedMaterial['material_code'] == 'RM-017') {
      this.balance_qty = 326;
    } else {
      this.balance_qty = +this.selectedMaterial['batch_qty'];
    }

    let _balQty = this.balance_qty;
    for (let i = 0; i < this.available_ars_data.length; i++) {
      if (this.balance_qty <= 0) {
        break;
      }




      if (this.selectedResult['calculation_type'] == 'LOD Basis') {
        if (Number(this.available_ars_data[i]['dry_qty']) < _balQty) {
          var calc_value = this.calculate_qty_to_dispense(this.available_ars_data[i]['balance_qty'], this.available_ars_data[i]['dry_qty']);
          this.available_ars_data[i]['qty_to_dispense'] = this.available_ars_data[i]['balance_qty'];
          _balQty = (_balQty - Number(this.available_ars_data[i]['dry_qty']));
          this.available_ars_data[i]['next_ar_qty'] = _balQty;
        } else {
          this.available_ars_data[i]['qty_to_dispense'] = _balQty;
          this.available_ars_data[i]['next_ar_qty'] = 0;//_balQty - Number( this.available_ars_data[i]['dry_qty']);
          _balQty = 0;
        }
      }

      else if (this.selectedResult['calculation_type'] == 'Assay Basis') {
        if (Number(this.available_ars_data[i]['pure_qty']) < _balQty) {
          var calc_value = this.calculate_qty_to_dispense(this.available_ars_data[i]['balance_qty'], this.available_ars_data[i]['pure_qty']);
          this.available_ars_data[i]['qty_to_dispense'] = this.available_ars_data[i]['balance_qty'];
          _balQty = (_balQty - Number(this.available_ars_data[i]['pure_qty']));
          this.available_ars_data[i]['next_ar_qty'] = _balQty;
        } else {
          let rev_calc = ((Number(this.available_ars_data[i]['balance_qty']) * _balQty) / Number(this.available_ars_data[i]['pure_qty'])).toFixed(2);
          this.available_ars_data[i]['qty_to_dispense'] = rev_calc;
          this.available_ars_data[i]['next_ar_qty'] = 0;
          _balQty = 0;
        }
      } else {
        if (Number(this.available_ars_data[i]['balance_qty']) < _balQty) {
          this.available_ars_data[i]['qty_to_dispense'] = Number(this.available_ars_data[i]['balance_qty']);
          _balQty = (_balQty - Number(this.available_ars_data[i]['balance_qty']));
          this.available_ars_data[i]['next_ar_qty'] = _balQty;
        } else {
          this.available_ars_data[i]['qty_to_dispense'] = this.available_ars_data[i]['balance_qty'];
          this.available_ars_data[i]['next_ar_qty'] = _balQty - Number(this.available_ars_data[i]['balance_qty']);;
        }
      }

    }


    this.gross_total = 0;
    this.net_total = 0;
    for (let i = 0; i < this.available_ars_data.length; i++) {
      if (this.balance_qty <= 0) {
        break;
      }


      var intact_containers: number = +this.available_ars_data[i]['intact_containers'];
      let loose_qty = +this.available_ars_data[i]['loose_Qty'];
      let ar__qty = +this.available_ars_data[i]['balance_qty'];
      if (ar__qty > this.balance_qty) {
        for (let x = 0; x < this.available_ars_data[i]['intact_containers']; x++) {
          let each_container_size = (this.available_ars_data[i]['pack_size'])
          if (this.balance_qty <= 0) {
            break;
          }
          if (this.balance_qty > each_container_size) {
            let obj = {
              "ar_no": this.available_ars_data[i]['ar_no'],
              "container_no": last_container_id + 1,
              "container_type": "Intact",
              "gross_wt": 0,
              "tare_wt": 0,
              "net_weight": each_container_size
            }
            this.available_ars.push(obj);
            this.gross_total = (Number(this.gross_total) + Number(each_container_size));
            this.net_total = (Number(this.net_total) + Number(each_container_size));
            this.balance_qty = (Number(this.balance_qty) - Number(each_container_size));
            last_container_id = last_container_id + 1;
          } else {
            let obj = {
              "ar_no": this.available_ars_data[i]['ar_no'],
              "container_no": last_container_id + 1,
              "container_type": "Loose",
              "gross_wt": 0,
              "tare_wt": 0,
              "net_weight": Number(this.balance_qty).toFixed(2)
            }
            this.available_ars.push(obj);

            this.gross_total = (Number(this.gross_total) + Number(this.balance_qty));
            this.net_total = (Number(this.net_total) + Number(this.balance_qty));


            this.balance_qty = 0;

          }
        }

      } else {
        let container_id = 0;
        for (let j = 0; j < intact_containers; j++) {
          let obj = {
            "ar_no": this.available_ars_data[i]['ar_no'],
            "container_no": j + 1,
            "container_type": "Intact",
            "gross_wt": 0,
            "tare_wt": 0,
            "net_weight": this.available_ars_data[i]['pack_size']
          }
          this.available_ars.push(obj);
          container_id += 1;
        }

        if (loose_qty > 0) {
          let obj = {
            "ar_no": this.available_ars_data[i]['ar_no'],
            "container_no": container_id + 1,
            "container_type": "Loose",
            "gross_wt": 0,
            "tare_wt": 0,
            "net_weight": loose_qty
          }
          this.available_ars.push(obj);
        }
        last_container_id = container_id + 1;

        this.balance_qty = (this.balance_qty - ar__qty);
        this.gross_total = (this.gross_total + ar__qty);
        this.net_total = (this.net_total + ar__qty);
      }






    }




    this.isStart = true;
    this.isView = false;
    this.isViewDispensing = false;

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
      this.gross_total = +(Number(this.gross_total) + Number(this.available_ars[i]['gross_wt']).toFixed(2));
      this.tare_total = +(Number(this.tare_total) + Number(this.available_ars[i]['tare_wt']).toFixed(2));
      this.net_total = +(Number(this.net_total) + Number(this.available_ars[i]['net_weight']).toFixed(2));
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
    //  this.selectedContainer = this.selectedMaterial['containers'];
    console.log(this.selectedContainer);
    this.service.get('store/dispensing.php?type=get_dispensing_Activity_By_Id&id=' + this.selectedMaterial["dispence_id"]).subscribe(response => {
      var data = response;
      this.available_ars_data = JSON.parse(data['ars']);
      this.available_ars = JSON.parse(data['containers']);
      this.isStart = false;
      this.isView = false;
      this.isViewDispensing = true;
    });

  }
  updateReceivingStatusForProduct(id,status) {
    this.service.get('production/dispensing.php?type=receive_material&id=' + id+ '&status='+status ).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Dispensing Checking Status Updated Successfully!');
        this.isDispensingCompleted = true;
        this.isView = false;
        this.getAcceptedRequests();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  remarks1;
  updateReceivingStatus(status) {
    let obj = {
      "remarks": this.remarks1,
      "rm_status" : status

    }
    this.service.post('production/dispensing.php?type=update_material_receiving_status&id=' + this.selectedResult['id'], JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Dispensing Checking Status Updated Successfully!');
        this.isDispensingCompleted = true;
        this.isView = false;
        this.getAcceptedRequests();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  print(data) {
    this.service.open('store/dispensing.php?type=printLabel&material_code=' + data + '&id=' + this.selectedResult['id']);
  }



}
