import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RmpmindentComponent } from './rmpmindent.component';

describe('RmpmindentComponent', () => {
  let component: RmpmindentComponent;
  let fixture: ComponentFixture<RmpmindentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RmpmindentComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RmpmindentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
