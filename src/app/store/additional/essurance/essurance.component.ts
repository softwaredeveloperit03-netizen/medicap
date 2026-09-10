import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-essurance',
  templateUrl: './essurance.component.html',
  styleUrls: ['./essurance.component.css']
})
export class EssuranceComponent implements OnInit {

  isView= false;
  selectedResult: [];
  results;
  remark;
  isStart = false;
  isViewDispensing = false;
  isViewshow = false;
  selectedContainer = [];
  available_ars_data = [];
  available_ars = [];
  selectedMaterial = [];
  fifo_method = '';
  balance_qty = 0;
  gross_total = 0;
  net_total = 0;
  employees;

  constructor(private service: DataAccessService) { }


 
  ngOnInit() { 
    this.getData();
   
  }
    getData() {
     
      this.service.get('production/additional_material.php?type=get_request_materials_for_approval').subscribe(response => {
        this.results = response;
      
      });
    }

    saveform(Form){
      if (!Form.valid) {
        alertify.error('All fields are required');
        return;
      }
      let temp = Form.value;
    temp['matrial']=this.selectedResult
      this.service.post('production/additional_material.php?type=saveIssuance', JSON.stringify(temp)) 
      .subscribe(response => {
        if (response['status'] === 'success') {       
          Form.resetForm();
          alertify.success("save successfully");
        } else {
          alertify.error('Please Try Again');
        }
        })
      }


  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  viewshow(index) {
    this.selectedResult = this.results[index];
    this.isViewDispensing = true;
  }

  approve(Form) {
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = Form.value;
  temp['material_type']=this.selectedResult['material_type']
  temp['material_name']=this.selectedResult['material_name']
  temp['material_code']=this.selectedResult['material_code']
  temp['qty']=this.selectedResult['qty']
  temp['product']=this.selectedResult['product']
  temp['product_name']=this.selectedResult['product_name']
  temp['purpose']=this.selectedResult['purpose']
  temp['justification']=this.selectedResult['justification']
      this.service.post('production/additional_material.php?type=saveIssuance&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  reject() {
    if (this.selectedResult['material_type'] == '') {
      alertify.error('Select Type!');
      return;
    }

    this.service.post('production/additional_material.php?type=requestmaterial&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success("update successfully");
        // this.getPendingPO();
        this.remark = '';
       
      } else {
        alertify.error('Failed to Update PO, Please try again!');
      }

      //  this.AllRecord();
    });
  }

  // viewshow(index) {
  //   let material = this.selectedResult['materials'];
  //   this.selectedMaterial = material[index];
  //   //  this.selectedContainer = this.selectedMaterial['containers'];
  //   console.log(this.selectedContainer);
  //   this.service.get('production/additional_material.php?type=get_dispensing_Activity_By_Id&id=' + this.selectedMaterial["dispence_id"]).subscribe(response => {
  //     var data = response;
  //     this.available_ars_data = JSON.parse(data['ars']);
  //     this.available_ars = JSON.parse(data['containers']);
  //     this.isStart = false;
  //     this.isView = false;
  //     this.isViewDispensing = true;
  //   });

  // }

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

  getStoreEmployees() {
    this.service.get('production/additional_material.php?type=getStoreEmployees').subscribe(response => {
      this.employees = response;
    });
  }

  print(data) {
    this.service.open('store/dispensing.php?type=printLabel&material_code=' + data + '&id=' + this.selectedResult['id']);
  }
  

  // updateDispensingStatus() {

  //   let temp = {};
  //   this.service.post('store/dispensing.php?type=update_dispense_complete_by_store&id=' + this.selectedResult['id'] + '&status=' + status, null).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alertify.success('Dispensing Completed Updated Successfully!');
  //       this.isDispensingCompleted = true;
  //       this.isView = false;
  //       this.getAcceptedRequests();
  //     } else {
  //       alertify.error('Failed: An error occured, please try again!');
  //     }
  //   });
  // }

}
