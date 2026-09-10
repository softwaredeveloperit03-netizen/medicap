import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RepairdescComponent } from './repairdesc.component';

describe('RepairdescComponent', () => {
  let component: RepairdescComponent;
  let fixture: ComponentFixture<RepairdescComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RepairdescComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RepairdescComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
