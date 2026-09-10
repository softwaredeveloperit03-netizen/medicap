import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PkcheclitComponent } from './pkcheclit.component';

describe('PkcheclitComponent', () => {
  let component: PkcheclitComponent;
  let fixture: ComponentFixture<PkcheclitComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PkcheclitComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PkcheclitComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
