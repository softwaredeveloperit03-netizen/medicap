import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

 
   isNew = false;
    
   constructor(private service: DataAccessService) { }
 
   ngOnInit() {
     this.getCandidate();
   }
 
   
   results;
   getCandidate() {
     this.service.get('hr/candidate.php?type=getRecommendedCandidatesForFurtherProcess').subscribe((response) => {
         this.results = response;
     });
   }
   
   selectedCandidate = [];
   isOffer = false;
  
   viewData(data){
     this.selectedCandidate = data;
     this.isNew = true;
   }

  selectedAnnexure = {};
  emp_id = '';

   generateOfferLetter(data){
     this.selectedCandidate = data;
      this.selectedAnnexure = {};
      this.emp_id = this.selectedCandidate['id'];
      this.selectedCandidate['operator_category'] = 'NA';
      this.selectedCandidate['empName'] = this.selectedCandidate['firstname']+' '+this.selectedCandidate['lastname'];

     this.isOffer = true;
   }

 
    netSalary = 0;

    annexureData = {};
    claculateAnnexureData() {
      this.service.get('hr/employee.php?type=claculateCandidateAnnexureData&annexureEmp_id=' + 
      encodeURIComponent(this.emp_id) +'&netPayableMOnthly=' + encodeURIComponent(this.netSalary)).subscribe(response => {
        this.annexureData = response;
      });
    }

    claculateAnnexureDataOnBaicDA() {
      this.service.get('hr/employee.php?type=claculateCandidateAnnexureDataOnBaicDA&annexureEmp_id=' + 
      encodeURIComponent(this.emp_id) +'&netPayableMOnthly=' + encodeURIComponent(this.netSalary)
      +'&basicDAMonthly=' + encodeURIComponent(this.annexureData['basicDAMonthly'])
      +'&conveyAllowanceMonthly=' + encodeURIComponent(this.annexureData['conveyAllowanceMonthly'])
      +'&eduAllowanceMonthly=' + encodeURIComponent(this.annexureData['eduAllowanceMonthly'])
      +'&foodAllowanceMonthly=' + encodeURIComponent(this.annexureData['foodAllowanceMonthly'])
      +'&dressAllowanceMonthly=' + encodeURIComponent(this.annexureData['dressAllowanceMonthly'])
      +'&medicalAllowanceMonthly=' + encodeURIComponent(this.annexureData['medicalAllowanceMonthly'])
      +'&othAllowanceMonthly=' + encodeURIComponent(this.annexureData['othAllowanceMonthly'])
      +'&monthlyBonusMonthly=' + encodeURIComponent(this.annexureData['monthlyBonusMonthly'])
      +'&bonusAnnually=' + encodeURIComponent(this.annexureData['bonusAnnually'])
      +'&latAnnually=' + encodeURIComponent(this.annexureData['latAnnually'])
      +'&performanceBonusMonthly=' + encodeURIComponent(this.annexureData['performanceBonusMonthly'])
    ).subscribe(response => {
        this.annexureData = response;
      });
    }




  saveSalaryAnnexureCandidate(data) {

      const temp = data;
      temp['grossSalAMonthly'] = this.annexureData['grossSalAMonthly'];
      temp['grossSalAAnnually'] = this.annexureData['grossSalAAnnually'];
      temp['totalRetrialMonthly'] = this.annexureData['totalRetrialMonthly'];
      temp['totalRetrialAnnually'] = this.annexureData['totalRetrialAnnually'];
      temp['netPayMonthly'] = this.annexureData['netPayMonthly'];
      temp['netPayAnuually'] = this.annexureData['netPayAnuually'];
      temp['ctcMonthly'] = this.annexureData['ctcMonthly'];
      temp['ctcAnuually'] = this.annexureData['ctcAnuually'];
       
      this.service.post('hr/candidate.php?type=saveSalaryAnnexureCandidate',JSON.stringify(temp)).subscribe((response: any) => {
        if (response.status === 'success') {
          alertify.success('Annexure Saved Successfully!!!!!!!!');
          this.annexureData = {};
        } else {
          alertify.error(response.status);
        }
      });
    }

    Offer_letter = 'Without Annexure';
    joining_date = '';
 
   saveGenerateOfferForm(annexureForm) {

      if (!annexureForm.valid) {
        alertify.error('All fields are required!');
        return;
      }
  
      const temp = annexureForm.value;     
      temp['candidate_id'] = this.selectedCandidate['id'];
 
    this.service.post('hr/candidate.php?type=saveGenerateOfferForm',JSON.stringify(temp)).subscribe((response) => {
         if (response['status'] == 'success') {
           alertify.success('Candidate Interview Scedule Successfully');
           this.isNew = false;
           this.getCandidate();

           if(temp['Offer_letter'] == 'With Annexure'){
              this.saveSalaryAnnexureCandidate(temp);
           }

           this.selectedCandidate = [];
         } else {
           alertify.error('An error occured, please try again');
         }
       });
   }
 
 
  
   viewResume(url) {
     url = this.service.url + '../..' + url;
     window.open(url, '_blank');
   }
  
 
   searchQuery;
 
   get filteredMaterials(): any[] {
     if (!this.searchQuery || this.searchQuery.trim() === '') {
       return this.results; // If search query is empty or whitespace, return all materials
     }
 
     const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
     return this.results.filter((material) => {
       // Check if any field of the material contains the search query
       return Object.entries(material).some(([key, value]) => {
         if (key === 'entry_date') {
           // Convert the value to a Date object if it's not already
           const dateValue = typeof value === 'string' ? new Date(value) : value;
           // Check if the date value is valid and includes the search query
           return (
             dateValue instanceof Date &&
             dateValue.toISOString().slice(0, 10).includes(query)
           );
         } else {
           // Convert field value to lowercase and check if it includes the search query
           return value && value.toString().toLowerCase().includes(query);
         }
       });
     });
   }
  
    
   viewCandidateOfferLetter(data){   
        this.service.open('hr/candidate.php?type=viewCandidateOfferLetter&id=' + data['id']);
   }
   
 
 
 }
 