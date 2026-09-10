import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RequalificationComponent } from './requalification.component';

describe('RequalificationComponent', () => {
  let component: RequalificationComponent;
  let fixture: ComponentFixture<RequalificationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RequalificationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RequalificationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
