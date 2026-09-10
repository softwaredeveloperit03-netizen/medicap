import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-form-new',
  templateUrl: './form-new.component.html',
  styleUrls: ['./form-new.component.css'],
})
export class FormNewComponent implements OnInit {
  checkListData;
  selectedCheckList;
  constructor(private service: DataAccessService,private route: ActivatedRoute) {}

  async ngOnInit(): Promise<any> {
    console.log('this.route.snapshot',this.route.snapshot);
    const id1 = this.route.snapshot.queryParams['id'];
    console.log('id',id1);
    // await this.getchecklistnew();
    // Retrieve the 'id' query parameter from the route
      const id = this.route.snapshot.queryParams['id'];
      this.selectedCheckList = this.checkListData[id];
      console.log('id',id);
      console.log('this.checkListData[id]',this.checkListData[id]);
     this.route.params.subscribe(params => {
        let data = params['id'];
        console.log('data :>> ', data);
    });
    this.route.snapshot.paramMap.get('id');
    console.log('this.route.snapshot. :>> ', this.route.snapshot.paramMap.get('id'));
    }

  getchecklistnew() {
    return new Promise((res,rej)=>{
      this.service
      .get('master/checklist.php?type=getMastercheckList')
      .subscribe((response: any) => {
        this.checkListData = response;
        res(response)
      });
    })
  }

}
