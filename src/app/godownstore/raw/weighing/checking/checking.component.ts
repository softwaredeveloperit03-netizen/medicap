import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {
  no=100;
  isView = false;
  results;
  weighing_remark;
  isProceed = false;
  batches = [];
  selectedReport = [];
  checkTableTh = {};
  checklist:any=[
   
  ];
  
  plant_id:any;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getCheckingWeighingMaterials();
   
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

  getCheckingWeighingMaterials() {
    this.service.get('store/raw.php?type=getCheckingWeighingMaterials').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
    this.getChkListData();
    console.log(this.selectedReport['id'])
  }


  weightData =[] ;
  viewDetails(index){
    let bat = this.selectedReport['batches'];
    this.batches = bat[index];
   
    console.log('btches' ,this.batches);
    console.log(bat[index].weight);
    let weightData = JSON.parse(bat[index].weight);
      this.weightData = weightData ;

      console.log(this.weightData);
    
    this.isProceed = true;
  }

  update(status) {
    this.service.post('store/raw.php?type=updateWeighing&id=' + this.selectedReport['cm_id'] + '&status=' + status+'&weighing_remark='+this.weighing_remark+'&challan_id='+this.selectedReport['challan_id'], JSON.stringify(this.selectedReport['weighing_details'])).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Weighing Record Updated Successfully');
        this.isView = false;
        this.getCheckingWeighingMaterials();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  getChkListData() {
    this.service.get('master/checklist.php?type=get_wgh_ChkListByTranID&tranId='+this.selectedReport['challan_id']).subscribe(response => {
      this.checklist = response;
    });
  }

}
