import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CmdashboardComponent } from './cmdashboard.component';

describe('CmdashboardComponent', () => {
  let component: CmdashboardComponent;
  let fixture: ComponentFixture<CmdashboardComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CmdashboardComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CmdashboardComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
