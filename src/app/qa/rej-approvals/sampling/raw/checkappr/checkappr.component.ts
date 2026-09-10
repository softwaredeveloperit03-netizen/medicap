import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-checkappr',
  templateUrl: './checkappr.component.html',
  styleUrls: ['./checkappr.component.css']
})
export class CheckapprComponent implements OnInit {
  isView = false;
  results;
  checkPointData: any;
  ComponentName: any;
  selectedSampling:any ;
  checklist_data:any=[];
  area_dtl: Object;
  sampleData_prev: Object;
  selectedSampling_prev;
  // getSamplingDetail_prev: any;
  constructor(private service: DataAccessService) { }

   
  ngOnInit() {
    this.getCheckedSamplings();
    this.getCheckPointData();
    this.ComponentName = 'sampling/raw/approval';
    // this.service.checkUserAccess(this.ComponentName);
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

  getCheckPointData(){

    this.service.get('master/checklist.php?type=getCheckPointByForm&module=Grn&form=GRN Checking').subscribe(response => {
     this.checkPointData = response;

   });

 }

  getCheckedSamplings() {
    this.service.get('qc/sampling.php?type=getCheckedSamplingsd').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isView = true;
    this.getSamplingDetail( this.selectedSampling.id)
    this.getSamplingDetail_prev(this.selectedSampling["id"]-1);

  }
  getSamplingDetail_prev(id) {
    this.service.get('qc/sampling.php?type=getActiveSamplings_prev&id='+id).subscribe(response => {
      this.sampleData_prev = response;
       this.selectedSampling_prev=this.sampleData_prev[0] ;
      console.log(this.selectedSampling_prev.material_code);
    });
  }

  sampleData:any = [] ;
  getSamplingDetail(id) {
    this.service.get('qc/sampling.php?type=getSamplingDetailById&id='+id).subscribe(response => {
      this.sampleData = response;
      this.selectedSampling.sampling_details = response ;
      // console.log(this.sampleData.reserve_composite);
      this.checklist_data=this.sampleData['checkPointData']
      console.log(this.checklist_data)
    });
    this.service.get('qc/sampling.php?type=getarea_details_by_id&id='+id).subscribe(response => {
      this.area_dtl = response;
      // this.selectedSampling.sampling_details = response ;
      console.log(this.sampleData.reserve_composite);
    });
  }

  updateSampling(status,remarktbl) {
    let rem = remarktbl.value;
    let temp = {};
    temp["sampling_no"] = this.selectedSampling['sampling_no'];
    temp["ar_no"] = this.selectedSampling['ar_no'];
    temp["grn_no"] = this.selectedSampling['grn_no'];
    temp["specification_no"] = this.selectedSampling['specification_no'];
    temp["grn_no"] = this.selectedSampling['grn_no'];
    temp["material_code"] = this.selectedSampling['material_code'];
    temp['checklist'] = this.checkPointData;
    temp["remark1"] = rem.remark1;

    this.service.post('qc/sampling.php?type=updateCheckedSampling&status=' + status + '&id=' + this.selectedSampling['id']+'&undertest_qty='+this.selectedSampling['undertest_qty']+'&grn_no='+this.selectedSampling['grn_no'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getCheckedSamplings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
