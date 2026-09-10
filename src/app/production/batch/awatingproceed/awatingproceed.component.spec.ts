import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AwatingproceedComponent } from './awatingproceed.component';

describe('AwatingproceedComponent', () => {
  let component: AwatingproceedComponent;
  let fixture: ComponentFixture<AwatingproceedComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AwatingproceedComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AwatingproceedComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
