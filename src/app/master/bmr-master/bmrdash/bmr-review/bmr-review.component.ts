import { Component, OnInit,AfterViewInit  } from '@angular/core';
import { ActivatedRoute, Router,Params } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DataService } from '../data.service'

declare let alertify;
@Component({
  selector: 'app-bmr-review',
  templateUrl: './bmr-review.component.html',
  styleUrls: ['./bmr-review.component.css']
})
export class BmrReviewComponent implements OnInit  {

  constructor(private service: DataAccessService, private route:ActivatedRoute, private router: Router,private dataService: DataService) { }
  selectedResult: any = {};

  ngOnInit(): void {


    // this.route.paramMap.subscribe(params => {
    //   this.getProcessStage(params.get('id'));
    // });



    this.selectedResult = this.dataService.getData() || {};
    console.log('Service data found:', this.selectedResult);


    if (!this.selectedResult || Object.keys(this.selectedResult).length === 0) {
      // Keep user on valid parent route when transient data is unavailable.
      this.selectedResult = {};
    }

    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.getProcessStage(id);
      } else {
        this.Stages = [];
      }
    });


  }

  stagemaster: any[] = [];
  Stages: any[] = [];

  getProcessStage(id){
    this.service
      .get('bmr/process.php?type=getProcesses_viewZuma&status=Pending&id=' + id)
      .subscribe({
        next: (response: any) => {
          this.bindStagesFromResponse(response);
        },
        error: () => {
          // Backward-compatible fallback for environments without Zuma route.
          this.service
            .get('bmr/process.php?type=getProcesses_view&id=' + id)
            .subscribe({
              next: (response: any) => this.bindStagesFromResponse(response),
              error: () => {
                this.stagemaster = [];
                this.Stages = [];
                alertify.error('Unable to load process stages.');
              },
            });
        },
      });
  }

  private bindStagesFromResponse(response: any): void {
    this.stagemaster = Array.isArray(response) ? response : [];
    const first = this.stagemaster.length > 0 ? this.stagemaster[0] : {};
    this.Stages = Array.isArray(first?.Stages) ? first.Stages : [];

    if ((!this.selectedResult || Object.keys(this.selectedResult).length === 0) && first) {
      this.selectedResult = first;
    }
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


      let temp ={};
      temp['product_code']=this.selectedResult['product_code']
      temp['id']=this.selectedResult['process_id']
      temp['status']=status;
      temp['stages']=this.Stages;
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