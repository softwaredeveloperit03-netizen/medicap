import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-revision',
  templateUrl: './revision.component.html',
  styleUrls: ['./revision.component.css']
})
export class RevisionComponent implements OnInit {

  constructor(private service: DataAccessService) { }
  
  ngOnInit(): void {
    this.getDetails();
  }

  data;
  getDetails()
  {
    this.service.get('qa/all2.php?type=get_revision').subscribe((response:any) => {
      this.data = response;
     
    });
  }

  sopId;sopTitle;revNumber;revDate;reasonForRev;SummaryOfChange;revOfficer;reviewDate;approval_status
  isView=false;
  selectedResult=[];
  view(index){
    this.selectedResult=this.data[index]
    this.isView=true;
    this.sopId=this.selectedResult['sopId'];
    this.sopTitle=this.selectedResult['sopTitle'];
    this.revNumber=this.selectedResult['revNumber'];
    this.revDate=this.selectedResult['revDate'];
    this.reasonForRev=this.selectedResult['reasonForRev'];
    this.SummaryOfChange=this.selectedResult['SummaryOfChange'];
    this.revOfficer=this.selectedResult['revOfficer'];
    this.reviewDate=this.selectedResult['reviewDate'];
    this.approval_status=this.selectedResult['approval_status'];
    console.log(this.selectedResult);
  }

}
