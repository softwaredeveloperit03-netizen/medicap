import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ChngcontrolQAComponent } from './chngcontrol-qa.component';

describe('ChngcontrolQAComponent', () => {
  let component: ChngcontrolQAComponent;
  let fixture: ComponentFixture<ChngcontrolQAComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ChngcontrolQAComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ChngcontrolQAComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
