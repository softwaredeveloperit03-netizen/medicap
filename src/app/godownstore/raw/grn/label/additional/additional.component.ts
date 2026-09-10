import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-additional',
  templateUrl: './additional.component.html',
  styleUrls: ['./additional.component.css'],
  providers:[DatePipe]
})
export class AdditionalComponent implements OnInit {

  from_date = '';
  to_date = '';
  isView = false;
  materials;
  results;
  results1;
  material_name = '';
  vendor_name = '';
  batches=[];
  grndetails=[];
  selectedReport = [];
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd'); }

  ngOnInit() {
    this.getGRNLog();
    this.getMaterials();
  }

  getGRNLog() {
    this.service.get('store/raw.php?type=getGRNLog&material_name=' + this.material_name + '&vendor_name=' + this.vendor_name).subscribe(response => {
      this.results = response;
      this.results1 = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.grndetails=this.selectedReport['grn_details'];
    this.batches=this.selectedReport['batches'];
    console.log(this.batches['batch_no']);
    this.isView = true;
  }

  print(result){
    this.service.open('store/label.php?type=printGRNAdditionalLabel&id='+this.selectedReport['id']+'&material_code='+this.selectedReport['material_code'] + '&batch_no=' + result['batch_no'] + '&total_containers=' + result['no_of_label'] + '&reason=' + result['reason']);
  }

  getMaterials() {
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials = response;
    })
  }

  
  filterStock(){
    this.results = [];
    for(let i=0; i<this.results1.length; i++){
      let data = this.results1[i];
      if(data.material_name.toUpperCase().includes(this.material_name.toUpperCase()) && data.vendor_name.toUpperCase().includes(this.vendor_name.toUpperCase()) ){
        this.results.push(data);
      }
    }
  }


  clear(){
    this.material_name = '';
    this.vendor_name = '';
    this.results = this.results1;
  }
}
