import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RejectlogComponent } from './rejectlog.component';

describe('RejectlogComponent', () => {
  let component: RejectlogComponent;
  let fixture: ComponentFixture<RejectlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RejectlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RejectlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
