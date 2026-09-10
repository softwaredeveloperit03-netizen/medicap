import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReqlogComponent } from './reqlog.component';

describe('ReqlogComponent', () => {
  let component: ReqlogComponent;
  let fixture: ComponentFixture<ReqlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReqlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReqlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
