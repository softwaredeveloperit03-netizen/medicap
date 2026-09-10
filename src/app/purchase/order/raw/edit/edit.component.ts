import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { ActivatedRoute, Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class EditComponent implements OnInit {

  isView = false;
  selectedResult = [];

  materials = [];
  isEdit =false;
  selectedMaterial = [];
  materialdata=[];
  gross_total = 0;
  gst_total = 0;
  net_total = 0;
  constructor(private service: DataAccessService, private _Activatedroute: ActivatedRoute,private router:Router) { }

  ngOnInit() {
    this._Activatedroute.paramMap.subscribe(params => {
      this.getPODetails(params.get('id'));
    });
  }

  getPODetails(id) {
    this.service.get('purchase/po/raw.php?type=getPODetails&id=' + id).subscribe((response: any) => {
      this.selectedResult = response;

      this.gross_total = this.selectedResult['gross_total'];
      this.gst_total = this.selectedResult['gst_total'];
      this.net_total = this.selectedResult['net_total'];

      this.materials = this.selectedResult['materials'];
      this.isView = true;
    });
  }

  update() {
    if (this.materials.length == 0) {
      alertify.error("Material List is Empty");
      return;
    }
    this.selectedResult['gross_total'] = this.gross_total;
    this.selectedResult['gst_total'] = this.gst_total;
    this.selectedResult['net_total'] = this.net_total;
    this.selectedResult['materials'] = this.materials;
    this.service.post('purchase/po/raw.php?type=ammendPO', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        this.router.navigate(['/purchase/order/raw/log'])
        alertify.success("Purchase Order Updated successfully!");
      } else {
        alertify.error("Failed: An error occured, please try again!");
      }
    });
  }

  edit(index) {
    this.selectedMaterial=this.materials[index];
    this.isEdit = true;
   // this.materials.splice(index, 1);
  }

  del(index) {
    this.materials.splice(index, 1);
    this.gross_total = this.gross_total*1-this.gross_total*1;
    this.gst_total = this.gst_total*1- this.gst_total*1;
    this.net_total = this.net_total*1-this.net_total*1;
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.selectedMaterial['qty'] = temp['qty'];
    this.materials[this.materials.length] = this.selectedMaterial;
    this.isEdit = false;
    this.selectedMaterial = [];
  }

  calculation() {
    if (this.selectedMaterial['quotation_per'] == "mg" || this.selectedMaterial['quotation_per'] == "Mg") {
      this.selectedMaterial['gross_total'] = +this.selectedMaterial['qty'] * (+this.selectedMaterial['quotation_amt']*1000);
    } else {
      this.selectedMaterial['gross_total'] = +this.selectedMaterial['qty'] * (+this.selectedMaterial['quotation_amt']);
    }
    this.selectedMaterial['tax_total'] = +this.selectedMaterial['gross_total'] * (+this.selectedMaterial['gst'] / 100);
    this.selectedMaterial['net_total'] = +this.selectedMaterial['gross_total'] + +this.selectedMaterial['tax_total'];

    this.gross_total = 0;
    this.gst_total = 0;
    this.net_total = 0;

    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      this.gross_total += +material['gross_total'];
      this.gst_total += +material['tax_total'];
      this.net_total += +material['net_total'];
    }
    this.gross_total += +this.selectedMaterial['gross_total'];
    this.gst_total += +this.selectedMaterial['tax_total'];
    this.net_total += +this.selectedMaterial['net_total'];
  }

}
