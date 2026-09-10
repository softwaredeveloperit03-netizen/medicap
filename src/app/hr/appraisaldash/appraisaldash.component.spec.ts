import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AppraisaldashComponent } from './appraisaldash.component';

describe('AppraisaldashComponent', () => {
  let component: AppraisaldashComponent;
  let fixture: ComponentFixture<AppraisaldashComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AppraisaldashComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AppraisaldashComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
