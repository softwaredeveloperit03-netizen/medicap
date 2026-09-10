import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css'],
})
export class AwaitingComponent implements OnInit {
  results;
  designations;
  qualifications;
  isView = false;
  selectedReport = [];
  decision = '';
  // attributes = [
  //  {"attribute":"Qualification and Aaptitude","question": "Academic background and enthusiasm for the position ?", "rating": 0  },
  //  {"attribute":"Experience", "question": "Job knowledge and Relevant experience for the position ?", "rating": 0  },
  //  {"attribute":"Leadership Skills" ,"question": "Ability to Manage, Develop and Motivate the team for getting the desired results?", "rating": 0 },
  //  {"attribute":"Achievement Orientation", "question": "Concern for working well or for surpassing a standard of excellence? ", "rating": 0 },
  //  {"attribute":"Decision Making Skills","question": "Ability to take right decision at right time with responsibility for proper functioning of the business?", "rating": 0 },
  //  {"attribute":"Influencing & Communication Skills", "question": "Building Relationships and nurturing networks internally & externally to persuade and influence others to agree to one’s point of view ?", "rating": 0 },
  //  {"attribute":"Analytical bent of mind", "question": "Logic in presenting opinion/views; interpretation of phenomena; mental alertness ?", "rating": 0 },
  //  {"attribute":"Technical Skills",  "question": "Knowledge & capabilities to perform specialized tasks ?", "rating": 0 },
  //  {"attribute":"Negotiation Skills","question": "Ability to negotiate and conclude with a Win-Win approach ?", "rating": 0 },
  //  {"attribute":"Initiative","question": "Ability to identify a problem, obstacle or opportunity and take action in light of this identification to address current or future problems or opportunities ? ", "rating": 0 },
  //  {"attribute":"Customer Service Orientation", "question": "Ability to understand the customer’s or client’s needs and respond appropriately and address underlying customer needs ? ", "rating": 0  },
  //  {"attribute":"Integrity","question": "Ability to act in a way that is consistent with what one says is important, i.e. one’s behavior is consistent with one’s values ?", "rating": 0  },
  //  {"attribute":"Personality" ,"question": "Attitude towards others; ability to get along with people; ability to influence ?", "rating": 0},
  //  {"attribute":"Potential","question": "Ability to advance and shoulder greater responsibilities ?", "rating": 0 }
  // ];

  questions = [
    {
      question: 'Academic background and enthusiasm for the position ?',
      rating: 0,
    },
    {
      question: 'Job knowledge and Relevant experience for the position ?',
      rating: 0,
    },
    {
      question:
        'Ability to Manage, Develop and Motivate the team for getting the desired results?',
      rating: 0,
    },
    {
      question:
        'Concern for working well or for surpassing a standard of excellence? ',
      rating: 0,
    },
    {
      question:
        'Ability to take right decision at right time with responsibility for proper functioning of the business?',
      rating: 0,
    },
    {
      question:
        'Building Relationships and nurturing networks internally & externally to persuade and influence others to agree to one’s point of view ?',
      rating: 0,
    },
    {
      question:
        'Logic in presenting opinion/views; interpretation of phenomena; mental alertness ?',
      rating: 0,
    },
    {
      question: 'Knowledge & capabilities to perform specialized tasks ?',
      rating: 0,
    },
    {
      question: 'Ability to negotiate and conclude with a Win-Win approach ?',
      rating: 0,
    },
    {
      question:
        'Ability to identify a problem, obstacle or opportunity and take action in light of this identification to address current or future problems or opportunities ? ',
      rating: 0,
    },
    {
      question:
        'Ability to understand the customer’s or client’s needs and respond appropriately and address underlying customer needs ? ',
      rating: 0,
    },
    {
      question:
        'Ability to act in a way that is consistent with what one says is important, i.e. one’s behavior is consistent with one’s values ?',
      rating: 0,
    },
    {
      question:
        'Attitude towards others; ability to get along with people; ability to influence ?',
      rating: 0,
    },
    {
      question: 'Ability to advance and shoulder greater responsibilities ?',
      rating: 0,
    },
  ];
  checkPointData;
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getPendingInterviews();
    this.getDesignations();
    this.getApprovedQualifications();
    this.getCheckPointData();
  }
  getCheckPointData() {
    this.service
      .get(
        'master/checklist.php?type=getCheckPointByForm&module=Interviewer&form=Interviewer Round'
      )
      .subscribe((response) => {
        this.checkPointData = response;
      });
  }

  getPendingInterviews() {
    this.service
      .get('hr/interview.php?type=getPendingInterviews')
      .subscribe((response) => {
        this.results = response;
      });
  }
  getApprovedQualifications() {
    this.service
      .get('common.php?type=getQualifications')
      .subscribe((response) => {
        this.qualifications = response;
      });
  }
  getDesignations() {
    this.service
      .get('hr/employee.php?type=getDesignations')
      .subscribe((response) => {
        this.designations = response;
      });
  }
  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  save(data) {
    // if (!data.valid) {
    //   alertify.error('An error occured, please try again!');
    //   return;
    // }

    let rating = 0;
    for (let i = 0; i < this.checkPointData.length; i++) {
      rating = Number(rating) + Number(this.checkPointData[i]['rating']);
    }
    if (rating == 0) {
      alertify.error('Please select atleast one answer proceed!');
      return;
    }
    if (data.value['decision'] == '' || data.value['decision'] == undefined) {
      alertify.error('Please select decision!');
      return;
    }

    let obj = {
      id: this.selectedReport['id'],
      // "attribute" :data.value['attribute'],
      //"attribute" :this.attributes,
      remarks: data.value['decision'],
      checklist: this.checkPointData,
    };

    this.service
      .post('hr/interview.php?type=Interviewer_Remarks', JSON.stringify(obj))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.resetForm();
          this.router.navigate(['/recruitment']);
          alertify.success('candidate details updated Successsfuly');
          data.resetForm();
          this.getPendingInterviews();
          // this.attributes =[
          //   {"attribute":"Qualification and Aaptitude","question": "Academic background and enthusiasm for the position ?", "rating": 0  },
          //   {"attribute":"Experience", "question": "Job knowledge and Relevant experience for the position ?", "rating": 0  },
          //   {"attribute":"Leadership Skills" ,"question": "Ability to Manage, Develop and Motivate the team for getting the desired results?", "rating": 0 },
          //   {"attribute":"Achievement Orientation", "question": "Concern for working well or for surpassing a standard of excellence? ", "rating": 0 },
          //   {"attribute":"Decision Making Skills","question": "Ability to take right decision at right time with responsibility for proper functioning of the business?", "rating": 0 },
          //   {"attribute":"Influencing & Communication Skills", "question": "Building Relationships and nurturing networks internally & externally to persuade and influence others to agree to one’s point of view ?", "rating": 0 },
          //   {"attribute":"Analytical bent of mind", "question": "Logic in presenting opinion/views; interpretation of phenomena; mental alertness ?", "rating": 0 },
          //   {"attribute":"Technical Skills",  "question": "Knowledge & capabilities to perform specialized tasks ?", "rating": 0 },
          //   {"attribute":"Negotiation Skills","question": "Ability to negotiate and conclude with a Win-Win approach ?", "rating": 0 },
          //   {"attribute":"Initiative","question": "Ability to identify a problem, obstacle or opportunity and take action in light of this identification to address current or future problems or opportunities ? ", "rating": 0 },
          //   {"attribute":"Customer Service Orientation", "question": "Ability to understand the customer’s or client’s needs and respond appropriately and address underlying customer needs ? ", "rating": 0  },
          //   {"attribute":"Integrity","question": "Ability to act in a way that is consistent with what one says is important, i.e. one’s behavior is consistent with one’s values ?", "rating": 0  },
          //   {"attribute":"Personality" ,"question": "Attitude towards others; ability to get along with people; ability to influence ?", "rating": 0},
          //   {"attribute":"Potential","question": "Ability to advance and shoulder greater responsibilities ?", "rating": 0 }
          //  ];
          this.questions = [
            //       { "question": "Academic background and enthusiasm for the position ?", "rating": 0 },
            // { "question": "Job knowledge and Relevant experience for the position ?", "rating": 0 },
            // { "question": "Ability to Manage, Develop and Motivate the team for getting the desired results?", "rating": 0 },
            // { "question": "Concern for working well or for surpassing a standard of excellence? ", "rating": 0 },
            // { "question": "Ability to take right decision at right time with responsibility for proper functioning of the business?", "rating": 0 },
            // { "question": "Building Relationships and nurturing networks internally & externally to persuade and influence others to agree to one’s point of view ?", "rating": 0 },
            // { "question": "Logic in presenting opinion/views; interpretation of phenomena; mental alertness ?", "rating": 0 },
            // { "question": "Knowledge & capabilities to perform specialized tasks ?", "rating": 0 },
            // { "question": "Ability to negotiate and conclude with a Win-Win approach ?", "rating": 0 },
            // { "question": "Ability to identify a problem, obstacle or opportunity and take action in light of this identification to address current or future problems or opportunities ? ", "rating": 0 },
            // { "question": "Ability to understand the customer’s or client’s needs and respond appropriately and address underlying customer needs ? ", "rating": 0 },
            // { "question": "Ability to act in a way that is consistent with what one says is important, i.e. one’s behavior is consistent with one’s values ?", "rating": 0 },
            // { "question": "Attitude towards others; ability to get along with people; ability to influence ?", "rating": 0 },
            // { "question": "Ability to advance and shoulder greater responsibilities ?", "rating": 0 }
          ];
          this.isView = false;
        } else {
          alertify.error('Errror Occured');
        }
      });
  }
  PrimarycheckPointData;
  getCheckPointDataprimary() {
    this.service
      .get(
        'master/checklist.php?type=getCheckPointByForm&module=Primary&form=Primary HR Round'
      )
      .subscribe((response) => {
        this.PrimarycheckPointData = response;
      });
  }
}
