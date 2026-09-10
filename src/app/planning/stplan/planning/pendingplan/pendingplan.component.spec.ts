import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PendingplanComponent } from './pendingplan.component';

describe('PendingplanComponent', () => {
  let component: PendingplanComponent;
  let fixture: ComponentFixture<PendingplanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PendingplanComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PendingplanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
