import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SelfLearnSubDashComponent } from './self-learn-sub-dash.component';

describe('SelfLearnSubDashComponent', () => {
  let component: SelfLearnSubDashComponent;
  let fixture: ComponentFixture<SelfLearnSubDashComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SelfLearnSubDashComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SelfLearnSubDashComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
