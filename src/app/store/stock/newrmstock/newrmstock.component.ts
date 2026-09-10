import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-newrmstock',
  templateUrl: './newrmstock.component.html',
  styleUrls: ['./newrmstock.component.css'],
  providers:[DatePipe]
})
export class NewrmstockComponent implements OnInit {
  
  isView = false;
  results;
  from_date = '';
  to_date = '';
  selectedResult = [];
  selectedGRN = [];
  issued = [];
  material_name = '';
  material_type = '';
  isView1 = false;
  results1=[];
  isNewIssue = false;
  products;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getMaterialOutDetails();
    this.getProducts();
  }

  
  getMaterialOutDetails() {
 
    this.service.get('store/bincard.php?type=NewgetMaterials&material_type=' + this.material_type).subscribe(response => {
      this.results = response;
      // this.filterEquipment();
      console.log(this.results);
      for(let i=0;i<this.results.length; i++ ){
        this.results[i].total_qty=Number(this.results[i].po_qty)+Number(this.results[i].EOU_STOCK);
      }
    });
  }
  // filterEquipment() {
  //   this.results1 = [];
  //   for (let i = 0; i < this.results.length; i++) {
  //     let material = this.results[i];
  //     if (material['material_type'].toUpperCase().includes(this.material_type.toUpperCase()) && material['material_name'].toUpperCase().includes(this.material_name.toUpperCase())) {
  //       this.results1[this.results1.length] = material;
  //     }
  //   }
  // }

  getProducts() {
    this.service.get('common.php?type=getProducts1').subscribe(response => {
      this.products = response;
    });
  }
  grns;
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;

    this.service.get('store/bincard.php?type=get_grn_stock_by_material_code&material_code=' + this.selectedResult['material_code']).subscribe(response => {
   
      this.grns = response;
     

    
    });
  }

  view1(index) {
    this.selectedResult = this.results[index];
    this.isView1 = true;
  }

  // view1(index) {
  //   let grns = this.selectedResult['grns'];
  //   this.selectedGRN = grns[index];
  //   this.issued = this.selectedGRN['issued'];
  //   this.isView1 = true;
  // }

  download() {
    this.service.open('store/bincard.php?type=downloadMaterialLog&material_type=' + this.material_type);
  }
  downloadp() {
    this.service.open('store/bincard.php?type=downloadView&id=' + this.selectedResult['id']);
  }
  downloadgrn() {
    this.service.open('store/bincard.php?type=downloadGrn&id=' + this.selectedResult['id']);
  }

  saveIssuedEntry(data) {
    if (!data.valid) {
      alertify.error('Invalid Data!');
      return;
    }
    let temp = data.value;
    temp['grn_no'] = this.selectedGRN['grn_no'];
    temp['ar_no'] = this.selectedGRN['ar_no'];
    temp['material_code'] = this.selectedGRN['material_code'];
    temp['unit'] = this.selectedGRN['unit'];
    this.service.post('store/bincard.php?type=issueMaterial', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully!');
        this.isNewIssue = false;
        this.isView = false;
        this.isView1 = false;
        data.reset();
        this.getMaterialOutDetails();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  downloadIssueRecords() {
    this.service.open('store/bincard.php?type=downloadMaterialBinCard&ar_no=' + this.selectedGRN['ar_no']);
  }

  clear(){
    this.material_type='';
    this.material_name='';
    // this.filterEquipment();
  }

}
