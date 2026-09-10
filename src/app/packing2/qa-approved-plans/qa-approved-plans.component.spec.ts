import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QaApprovedPlansComponent } from './qa-approved-plans.component';

describe('QaApprovedPlansComponent', () => {
  let component: QaApprovedPlansComponent;
  let fixture: ComponentFixture<QaApprovedPlansComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QaApprovedPlansComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QaApprovedPlansComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
