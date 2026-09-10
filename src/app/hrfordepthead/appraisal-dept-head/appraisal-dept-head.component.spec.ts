import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AppraisalDeptHeadComponent } from './appraisal-dept-head.component';

describe('AppraisalDeptHeadComponent', () => {
  let component: AppraisalDeptHeadComponent;
  let fixture: ComponentFixture<AppraisalDeptHeadComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AppraisalDeptHeadComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AppraisalDeptHeadComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
