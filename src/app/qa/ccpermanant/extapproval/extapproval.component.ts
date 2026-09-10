import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-extapproval',
  templateUrl: './extapproval.component.html',
  styleUrls: ['./extapproval.component.css']
})
export class ExtapprovalComponent implements OnInit {
  changeControlId;
    changeTitle: any;

  constructor(private service: DataAccessService, private router: Router) { }

  results;

  ngOnInit() {
    this.getExtension();
  }

  getExtension(){
    this.service.get('qa/all2.php?type=getExtension').subscribe((response:any) => {
      this.changeControlId = response;
     
    });
  }

  id;
  title;
  isView=false;
  selectedResult=[];
  view(index){
    this.selectedResult=this.changeControlId[index]
    this.isView=true;
    this.id=this.selectedResult['changeId'];
    this.title=this.selectedResult['changeTitle'];
    console.log(this.selectedResult);
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    this.service.post('qa/all2.php?type=saveExtApproval&changeControlID='+this.id, JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/qa/ccpermanant']);
        // data.resetForm();
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }
}
