import { Component, OnInit,AfterViewInit  } from '@angular/core';
import { ActivatedRoute, Router,Params } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DataService } from '../data.service'

declare let alertify;
@Component({
  selector: 'app-bmr-apprval',
  templateUrl: './bmr-apprval.component.html',
  styleUrls: ['./bmr-apprval.component.css']
})
export class BmrApprvalComponent implements OnInit {

  constructor(private service: DataAccessService, private route:ActivatedRoute, private router: Router,private dataService: DataService) { }
  selectedResult: any = {};

  ngOnInit(): void {


    // this.route.paramMap.subscribe(params => {
    //   this.getProcessStage(params.get('id'));
    // });



    this.route.paramMap.subscribe(params => {
      this.getProcessStage(params.get('id'));
    });
    this.selectedResult = this.dataService.getData() || {};
    console.log('Service data found:', this.selectedResult);

    // this.getProcessStage(this.selectedResult['manufacturing_process_id']);


  }

  stagemaster: any[] = [];
Stages: any[] = [];
entry_date;
entry_by;
review_by;
review_date;
approved_by;
approved_date;
  getProcessStage(id){
    if (!id) {
      return;
    }
    this.service.get('bmr/process.php?type=getProcesses_viewZuma&status=review&id='+id).subscribe({
      next: (response: any) => {
        this.stagemaster = Array.isArray(response) ? response : [];
        const first = this.stagemaster[0] || {};
        this.Stages = Array.isArray(first['Stages']) ? first['Stages'] : [];
        this.entry_by = first['entry_by'];
        this.entry_date = first['entry_date'];
        this.review_by = first['review_by'];
        this.review_date = first['review_date'];
        this.approved_by = first['approved_by'];
        this.approved_date = first['approved_date'];

        if (!this.selectedResult || Object.keys(this.selectedResult).length === 0) {
          this.selectedResult = first;
        }
      },
      error: () => {
        alertify.error('Unable to load approval data');
      }
    });
}

isEncapsule=false;
selectedPage = 0;
stepsss=false;
selectPage(index, index1) {
  this.selectedPage = index;
  if(index==13){
    this.isEncapsule=true;
    console.log('hellllo')
  }

  this.stepsss=false;

}
selectedStage = [];
selectStagePage(index) {
  this.selectedPage = 99;
  this.selectedStage = this.Stages[index];
  this.stepsss=true;
  console.log(this.stepsss);
}


completeMaster(status){
      const source =
        (this.selectedResult && Object.keys(this.selectedResult).length > 0)
          ? this.selectedResult
          : (this.stagemaster && this.stagemaster[0]) ? this.stagemaster[0] : {};

      const product_code = source['product_code'];
      const process_id = source['process_id'] || source['id'];

      if (!product_code || !process_id) {
        alertify.error('Unable to complete master. Missing process details.');
        return;
      }

      const temp: any = {};
      temp['product_code'] = product_code;
      temp['id'] = process_id;
      temp['status'] = status;
      temp['stages'] = Array.isArray(this.Stages) ? this.Stages : [];
  this.service.post('bmr_new/bmr.php?type=complete_bmr_masterZuma',JSON.stringify(temp)).subscribe(response =>{
    if(response['status']=='success') {
      this.router.navigate(['/master'])

      alertify.success('SAVE');
    } else{
      alertify.error(response['msg']);
    }
  });
}

}