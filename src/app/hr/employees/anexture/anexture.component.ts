import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-anexture',
  templateUrl: './anexture.component.html',
  styleUrls: ['./anexture.component.css']
})
export class AnextureComponent implements OnInit {

    constructor(private service: DataAccessService, private router: Router) {}

 
    ngOnInit() {
      this.getDepartments();
      this.getAnnexureOfEmployeeByDept();
    }

    isNew = false;

    departments;
    getDepartments() {
        this.service.get('hr/employee.php?type=get_department_by_designation').subscribe((response) => {
          this.departments = response;
        });
    }

    department = '';
  
    employees;
    getEmployeesByDepartment() {
      this.service.get('hr/employee.php?type=getEmployeesbydept&department_name='+this.department).subscribe(response => {
        this.employees = response;
      });
    }

    annexures;
    departmentForanX = 'ALL';
    getAnnexureOfEmployeeByDept() {
      this.service.get('hr/employee.php?type=getAnnexureOfEmployeeByDept&department_name='+this.departmentForanX).subscribe(response => {
        this.annexures = response;
      });
    }


    isView = false;
    selectedAnnexure = {};
    view(data){
      this.selectedAnnexure = {};
      this.selectedAnnexure = data;
      this.selectedAnnexure['empName'] = this.selectedAnnexure['firstname']+' '+this.selectedAnnexure['middlename']+' '+this.selectedAnnexure['lastname']
      this.isView = true;
    }

    netSalary = 0;

    annexureData = {};
    claculateAnnexureData() {
      this.service.get('hr/employee.php?type=claculateAnnexureData&annexureEmp_id=' + 
      encodeURIComponent(this.emp_id) +'&netPayableMOnthly=' + encodeURIComponent(this.netSalary)).subscribe(response => {
        this.annexureData = response;
      });
    }

    claculateAnnexureDataOnBaicDA() {
      this.service.get('hr/employee.php?type=claculateAnnexureDataOnBaicDA&annexureEmp_id=' + 
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

    selectedEmployee =[];
    emp_id = '';

    getEmployeeById(){
      this.selectedEmployee = this.employees.find(emp => emp.emp_id === this.emp_id);
      this.selectedEmployee['empName'] = this.selectedEmployee['firstname']+' '+this.selectedEmployee['middlename']+' '+this.selectedEmployee['lastname']
    }
 
    saveAnnexure(annexureForm) {
      if (!annexureForm.valid) {
        alertify.error('All fields are required!');
        return;
      }
      const temp = annexureForm.value;
      temp['grossSalAMonthly'] = this.annexureData['grossSalAMonthly'];
      temp['grossSalAAnnually'] = this.annexureData['grossSalAAnnually'];
      temp['totalRetrialMonthly'] = this.annexureData['totalRetrialMonthly'];
      temp['totalRetrialAnnually'] = this.annexureData['totalRetrialAnnually'];
      temp['netPayMonthly'] = this.annexureData['netPayMonthly'];
      temp['netPayAnuually'] = this.annexureData['netPayAnuually'];
      temp['ctcMonthly'] = this.annexureData['ctcMonthly'];
      temp['ctcAnuually'] = this.annexureData['ctcAnuually'];
      temp['pan'] = this.selectedEmployee['pan'];
      
      this.service.post('hr/employee.php?type=saveSalaryAnnexure',JSON.stringify(temp)).subscribe((response: any) => {
        if (response.status === 'success') {
          annexureForm.resetForm();
          alertify.success('Annexure Saved Successfully!!!!!!!!');
          this.annexureData = {};
          this.getAnnexureOfEmployeeByDept();
        } else {
          alertify.error(response.status);
        }
      });
    }
 
    searchQuery;
    get filteredMaterials(): any[] {
      if (!this.searchQuery || this.searchQuery.trim() === '') {
        return this.annexures; // If search query is empty or whitespace, return all materials
      }
      
      const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
    
      return this.annexures.filter(material => {
        // Check if any field of the material contains the search query
        return Object.entries(material).some(([key, value]) => {
          if (key === 'entry_date') {
            // Convert the value to a Date object if it's not already
            const dateValue = typeof value === 'string' ? new Date(value) : value;
            // Check if the date value is valid and includes the search query
            return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
          } else {
            // Convert field value to lowercase and check if it includes the search query
            return value && value.toString().toLowerCase().includes(query);
          }
        });
      });
    }




exportTableToPDFWithStyles() {
   let tableId = 'myTable';
    const table = document.getElementById(tableId);
    if (!table) {
        console.error(`Table with id "${tableId}" not found.`);
        return;
    }

    const printWindow = window.open('', '', 'height=600,width=800');
    if (!printWindow) {
        console.error('Unable to open print window');
        return;
    }

   let title = 'Annexure-A';

    // Clone the table to avoid modifying the original
    const clonedTable = table.cloneNode(true) as HTMLElement;

    // Copy computed styles recursively
    function copyStyles(source: HTMLElement, target: HTMLElement) {
        const computed = window.getComputedStyle(source);
        for (let i = 0; i < computed.length; i++) {
            const key = computed[i];
            (target.style as any)[key] = computed.getPropertyValue(key);
        }

        for (let i = 0; i < source.children.length; i++) {
            const srcChild = source.children[i] as HTMLElement;
            const tgtChild = target.children[i] as HTMLElement;
            copyStyles(srcChild, tgtChild);
        }
    }

    copyStyles(table as HTMLElement, clonedTable);

    // Write HTML into the print window
    printWindow.document.write(`
        <html>
            <head>
                <title>${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    @media print { button { display: none; } }
                </style>
            </head>
            <body>
                <h2>${title}</h2>
            </body>
        </html>
    `);

    printWindow.document.body.appendChild(clonedTable);

    printWindow.document.close();
   printWindow.onload = () => {
    printWindow.focus();
    printWindow.print();

    printWindow.onafterprint = () => {
      printWindow.close();
    };
  };
}

 
}



 

     