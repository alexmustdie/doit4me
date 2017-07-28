<?php

abstract class Bot extends VK
{
  protected
    $peers = [];

  private function __construct() {}

  public function connectLongPoll()
  {
    $lp = new LongPoll($this);
    $lp->getUpdates();
  }

  protected function inPeers($peer_id)
  {
    foreach ($this->peers as $i => $peer)
    {
      if ($peer_id == $peer->getId())
      {
        return $i;
      }
    }

    return false;
  }

  protected function getPeer($peer_id)
  {
    $i = $this->inPeers($peer_id);

    if ($i !== FALSE)
    {
      return $this->peers[$i];
    }

    return false;
  }

  protected function addPeer($peer)
  {
    if (!$this->inPeers($peer->getId()))
    {
      $this->peers[] = $peer;
      return true;
    }
 
    return false;
  }

  protected function deletePeer($peer_id)
  {
    $i = $this->inPeers($peer_id);

    if ($i !== FALSE)
    {
      unset($this->peers[$i]);
      return true;
    }

    return false;
  }
}

?>
