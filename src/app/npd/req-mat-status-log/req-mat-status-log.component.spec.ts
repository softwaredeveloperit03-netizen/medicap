import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReqMatStatusLogComponent } from './req-mat-status-log.component';

describe('ReqMatStatusLogComponent', () => {
  let component: ReqMatStatusLogComponent;
  let fixture: ComponentFixture<ReqMatStatusLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReqMatStatusLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReqMatStatusLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
