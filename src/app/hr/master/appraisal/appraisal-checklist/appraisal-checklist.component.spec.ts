import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AppraisalChecklistComponent } from './appraisal-checklist.component';

describe('AppraisalChecklistComponent', () => {
  let component: AppraisalChecklistComponent;
  let fixture: ComponentFixture<AppraisalChecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AppraisalChecklistComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AppraisalChecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
