import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-materials',
  templateUrl: './materials.component.html',
  styleUrls: ['./materials.component.css']
})
export class MaterialsComponent implements OnInit {
  stocks;
  vendors;

  vendor_no = '';
  material_type = '';
  grade = '';
  status = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getStock();
    this.getVendors();
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getStock(){
    this.service.get('store/packing.php?type=getStock&status='+this.status+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade).subscribe(response => {
      this.stocks = response;
    });
  }

  download(){
    this.service.open('pdf1/store.php?type=packingStockLog&status='+this.status+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade);
  }

}
