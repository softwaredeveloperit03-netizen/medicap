import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {
  vendor_name='';
  isView = false;
  results;
  vendors;
  selectedReport = [];
  vendor_no='';
  status='';
  material_type='';
  to_date='';
  from_date='';
  today='';
  material_subtype='';
  constructor(private service: DataAccessService,private datePipe: DatePipe) {

    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }
 
  ngOnInit(): void {
    this.getWeighingMaterials();
    this.getVendors();
  }

  getWeighingMaterials() {
    this.service.get('store/packing.php?type=getweighingLog&material_subtype='+this.material_subtype+'&vendor_name='+this.vendor_name+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  AllRecord(){
    this.service.get('store/packing.php?type=getAllWeighingMaterials').subscribe((response : any) => {
      this.results = response;
    });
    // this.material_subtype='';
    this.vendor_name='';       
  }
  download(){
    this.service.open('store/packing.php?type=downloadWeighingMaterialsLog');
  }
}
