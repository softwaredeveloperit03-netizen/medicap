import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ApprComponent } from './appr.component';

describe('ApprComponent', () => {
  let component: ApprComponent;
  let fixture: ComponentFixture<ApprComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ApprComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ApprComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
