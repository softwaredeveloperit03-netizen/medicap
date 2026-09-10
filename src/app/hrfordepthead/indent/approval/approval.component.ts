import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

 
 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
 
    this.getPendingIndends();
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
  
  results

  getPendingIndends() {
    this.service.get('purchase/indent.php?type=getIndentForApprovalDeptApproval&status=TO_HOD&department_name='+localStorage.getItem('department')).subscribe((response: any) => {
      this.results = response;
    });
  }

  isView = false;
  materials=[];
 
  selectedResult=[];

   view(index) {
    this.selectedResult = this.results[index];
    this.materials = this.selectedResult['materials'];
     this.isView = true;
  }

  dept_head_remark = '';
  hodRemark = '';

  approveIndend(status) {
     
    let temp ={};
    temp['materials'] = this.materials;
    temp['hodRemark'] = this.hodRemark;
 
    this.service.post('purchase/indent.php?type=approveIndentFromdept&status=' + status, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPendingIndends();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }


 





  revertapproveIndend(status) {

    
    if(this.dept_head_remark ==''){
      alertify.error('Please Add Remark');
      return;
    }
     
    let temp ={};
    temp['materials'] = this.materials;
    temp['dept_head_remark'] = this.dept_head_remark;

    this.service.post('purchase/indent.php?type=approveIndentFromdeptmisll&status=' + status, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.isrevert = false;
        this.getPendingIndends();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  isrevert = false;

  openRevert(){
    this.isrevert = true;

  }

  

}
