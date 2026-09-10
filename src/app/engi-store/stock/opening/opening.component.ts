import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-opening',
  templateUrl: './opening.component.html',
  styleUrls: ['./opening.component.css'],
})
export class OpeningComponent implements OnInit {

  isNew = false;
 
  constructor(private service: DataAccessService,private router:Router) {}

  ngOnInit() {
  
    this.getSubMaterials(this.material_type);
   this.getVendorsByMatType();
  }


  materials;
  productList = [];

  addProduct() {
    const selectedItems = this.materials.filter(item => item.selected);

    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }

    const invalids = selectedItems.filter(m =>
       m?.batch_no == '' || m?.qty <= 0 || m?.ar_no == '' || m?.grn_no == ''    
    );

    if (invalids.length) {
      alert('Some selected items are missing required fields!!!!');
      return;
    }

    selectedItems.forEach(item => {
      const isDuplicate = this.productList.some(
        product =>
          product.material_code === item.material_code &&
          product.batch_no === item.batch_no
      );

      if (!isDuplicate) {
        this.productList.push({
          material_name: item.material_name,
          material_code: item.material_code,
          batch_no: item.batch_no,
          pack_size: item.pack_size,
          qty: item.qty,
          unit: item.unit,
          ar_no: item.ar_no,
          grn_no: item.grn_no,
          grn_date: item.grn_date,
          assay: item.assay,
          mfg_date: item.mfg_date,
          exp_date: item.exp_date,
          release_date: item.release_date,
          vendor_no: item.vendor_no,
          clientGrpCode: item.clientGrpCode,
          clientSubGrpCode: 'NA',
          total_containers: 'NA',
        });
      }
    });

    // Unselect all items after adding
    this.materials.forEach(item => (item.selected = false));
  }

 
  material_type = 'Stationary';


  getSubMaterials(value) {
    this.service.get('common.php?type=getMaterialsforstockGenMaterials&material_type=' + value).subscribe(response => {
      this.materials = response;
    });
    
  }

  
  vendors;
  getVendorsByMatType(){
    this.service.get('common.php?type=getVendorsByMatTypeForGenOpening').subscribe(response => {
      this.vendors = response;
    });
  }

  
  saveOpeningStock(data) {
    if (this.productList.length == 0) {
      alert('Please Select At Least One Material To Proceed...');
      return;
    }
    let temp = {};
    temp['material_list'] = this.productList;
    this.service.post('store/dispensing.php?type=saveStockGeneralMaterial', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Saved Successfully');
        this.router.navigate(['/store']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
 
  }

  

}
