import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-equipment-vendor',
  templateUrl: './equipment-vendor.component.html',
  styleUrls: ['./equipment-vendor.component.css']
})
export class EquipmentVendorComponent implements OnInit {

  records;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getEquipmentVendor();
  }

  getEquipmentVendor() {
    this.service.get('qa.php?type=getEquipmentVendor').subscribe(response => {
      this.records = response;
    });
  }

}
