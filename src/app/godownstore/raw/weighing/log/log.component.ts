import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]

})
export class LogComponent implements OnInit {
  checklist:any=[
   
  ];
  plant_id:any;

  isView = false;
  results;
  challan_for = '';
  material_type = '';
  from_date = '';
  to_date = '';
  today = '';
  isProceed = false;
  batches = [];
  selectedReport = [];
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getWeighingMaterials();
    
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
  getChkListData(chid) {
    this.service.get('store/raw.php?type=get_wgh_ChkListByTranID&tranId='+chid).subscribe(response => {
      this.checklist = response;
      console.log('asd',this.checklist);
    });
  }

  getWeighingMaterials() {
    this.service.get('store/raw.php?type=getWeighingMaterials&material_type=' + this.material_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date ).subscribe(response => {
      this.results = response;
    });
  }
  
  view(index) {
    this.selectedReport = this.results[index];
    this.getChkListData(this.selectedReport["challan_id"]);
    this.isView = true;
  }

  viewDetails(index) {
    let bat = this.selectedReport['batches'];
    this.batches = bat[index];
    console.log('btches', this.batches);

    this.isProceed = true;
  }


  downloadLog() {
    this.service.open('store/raw.php?type=weighingMaterialLogPDF&material_type=' + this.material_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date + '&challan_for=' + this.challan_for)
  }

  downloadPDF(sign) {
    this.service.open('store/raw.php?type=weighingMaterialPDF&pdfsign=' + sign + '&id=' + this.selectedReport['id']);
  }
  downloadPDFdigital(sign) {
    this.service.open('store/raw.php?type=weighingMaterialdigitalPDF&pdfsign=' + sign + '&id=' + this.selectedReport['id']);
  }
  AllRecord(){
    this.service.get('store/raw.php?type=getAllWeighingMaterials').subscribe((response : any) => {
      this.results = response;
    });
    this.material_type='';
  }

}
