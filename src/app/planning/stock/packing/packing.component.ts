import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-packing',
  templateUrl: './packing.component.html',
  styleUrls: ['./packing.component.css']
})
export class PackingComponent implements OnInit {

  stocks;
  vendors;
 loading;
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
