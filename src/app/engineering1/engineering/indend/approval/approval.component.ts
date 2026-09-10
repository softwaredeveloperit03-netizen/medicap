import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
 
  isView = false;
  
   constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingIndends();
  }


  currentPage: number = 1;
  pageSize: number = 10;

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10);
  }
  viewf(){
    this.isView=false;
    this.currentPage=1;
    this.pageSize =10;
    
  }
  
  results;
  selectedResult =[];
  materials=[];
 
  getPendingIndends() {
    this.service.get('purchase/indent.php?type=getindentforcheking&from_department=Engineering').subscribe((response: any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.materials = this.selectedResult['materials'];
    this.isView = true;
  }


  approveIndend(status){


    const selectedItems = this.materials.filter((term) => term.selected);
    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }
    console.log(selectedItems);

 
    this.service.post('purchase/indent.php?type=checkingFromdepatment', JSON.stringify(selectedItems)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPendingIndends();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }
   
}
